<?php namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use Input;
use Redirect;
use App\Http\Controllers\BookController;
use App\Format;
use App\Book;

class EditBookRequest extends Request {

	const MAX_CONTENT_FILE_SIZE		= 10240; 	// 10240 kb = 10 mb
	const MAX_COVER_IMAGE_FILE_SIZE		= 500; 		//  500 kb

	/**
	* Determine if the user is authorized to make this request.
	*
	* @return bool
	*/
	public function authorize()
	{
		$input = $this->all();

		// Check the book to be edited exists and belongs to the current authenticated user.
		$bid = intval($input['bid']);
		if($book = Book::find($bid))
		{
			if(\Auth::check() && $book->uid == \Auth::user()->uid)
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Send a custom response when user authentication fails.
	*
	* @return Response
	*/
    public function forbiddenResponse()
    {
		// Return default 403 error page.
		abort(403);
    }

	/**
	* Overrides parent function to append custom validation.
	*
	* @return Validator
	*/
	public function getValidatorInstance()
	{
		$validator = parent::getValidatorInstance();

		// After sanitization and rule validation, perform additional validation on the uploads.
        $validator->after(function() use ($validator)
		{
			$input = $this->all();

			// At this point, the user is authenticated and owns the book given by BID.
			$bid = intval($input['bid']);

			//--------------------------------------------------------------------------
			// 	Perform additional file validation.
			//--------------------------------------------------------------------------

			// Check how many files the user currently has for this book.
			$formats = Format::where('bid', '=', $bid)->get();
			$num_formats = count($formats);

			// Check how many formats the user wishes to delete.
			$num_formats_to_delete = 0;

			// Check how many content files the user uploaded.
			$num_formats_uploaded = 0;

			foreach($input['content_files'] as $key => $file)
			{
				if(isset($file))
			 	{
					$num_formats_uploaded++;
				}

				$format = BookController::FORMATS[$key];
				if(isset($input['remove_' . $format]))
				{
					$num_formats_to_delete++;
				}
			}

			// Ensure the book will still have at least one uploaded content file.
			if((($num_formats + $num_formats_uploaded) - $num_formats_to_delete) <= 0)
			{
				$validator->errors ()->add('files', 'You must have at least one content file.');
			}

			// Get all files.
			$files = Input::file();

			// Manage content files.
			$content_files = $files['content_files'];

			foreach($content_files as $key => $file)
			{
				if(isset($file))
				{
					// Check that the file was uploaded successfully (no errors).
					if(!$file->isValid())
					{
						$validator->errors ()->add('content_files.' . $key, 'Invalid file.');
					}

					// Check extensions; mime checks already completed through rules.
					if($file->getClientOriginalExtension() != BookController::FORMATS[$key])
					{
						$validator->errors ()->add('content_files.' . $key, 'File extension should be .' . BookController::FORMATS[$key] . '.');
					}
				}
			}

			// If a cover was uploaded, check that its dimensions are valid.
			if(isset($files['image_file']))
			{
				$cover_file = $files['image_file'];

				// Check that the file was uploaded successfully (no errors).
				if(!$cover_file->isValid())
				{
					$validator->errors ()->add('image_file', 'Invalid file.');
				}

				// Check extensions; mime checks already completed through rules.
				if($cover_file->getClientOriginalExtension() != 'jpg' && $cover_file->getClientOriginalExtension() != 'jpeg')
				{
					$validator->errors ()->add('image_file', 'File extension should be .jpg.');
				}

				// Check if valid image size.
				if(!BookController::isValidCoverDimensions($cover_file))
				{
					$validator->errors ()->add('image_file', 'Ensure your cover image meets height and width requirements.');
				}
			}
		}
        );

        return $validator;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$this->sanitize();

		$general_rules = [
			'title' 		=> 'required|min:2|max:255',
			'co_authors' 		=> 'min:3|max:255',
			'series_name' 		=> 'min:2|max:255|required_with:series_number',
			'series_number' 	=> 'numeric|min:0|max:9999.99',
			'word_count' 		=> 'numeric|min:0|max:999999999',
			'page_count' 		=> 'numeric|min:0|max:999999999',
			'accept_terms' 		=> 'accepted',
			'language' 		=> 'alpha',
			'image_file' 		=> 'image|mimes:jpeg|min:0|max:' . CreateBookRequest::MAX_COVER_IMAGE_FILE_SIZE,
		];

		$file_rules = array();
		foreach(BookController::FORMATS as $key => $format)
		{
			$file_rules["content_files.$key"] = "mimes:$format|max:"  . CreateBookRequest::MAX_CONTENT_FILE_SIZE;
		}

		return array_merge($file_rules, $general_rules);

	}

	/**
	* Add customized error messages for the user depending on which rule condition failed.
	*
	* @return array
	*/
	public function messages()
	{
		$file_messages = array();

		foreach(BookController::FORMATS as $key => $format)
		{
			$file_messages["content_files.$key.mimes"] = 'Invalid mime type.';
			$file_messages["content_files.$key.max"] = 'Exceeded max file size of :max KB.';
		}

		$general_messages = [
			'co_authors.min' 			=> 'The co-author field must be at least :min characters.',
			'co_authors.max'			=> 'The co-author field must be at most :max characters.',
			'series_name.required_with' 		=> "You must enter a series name if you have a series number.",
			'series_number.min'			=> 'The series number must be a positive number.',
			'word_count.min'			=> 'The word count must be a positive number.',
			'page_count.min'			=> 'The page count must be a positive number.',
			'accept_terms.accepted'			=> 'You must accept the terms of agreement.',
			'image_file.mimes'			=> 'Invalid mime type.',
			'image_file.max'			=> 'Exceeded max file size of :max KB.',
		];

		return array_merge($file_messages, $general_messages);
	}

	/**
	* Sanitize book information (whitespace, tag array, empty strings, etc.)
	*
	* @postcondition Input is sanitized
	*/
	public function sanitize()
	{
		$input = $this->all();

		// Don't change file names. Save them for later.
		$files = $input['content_files'];
		unset($input['content_files']);

		// Allow synopsis to have more newlines, but not excessive newlines.
		$synopsis = trim(preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $input['synopsis']));

		// Trim all fields of excess whitespace.
		$input = array_map('trim', $input);
		$input = preg_replace('/[\s\t\n\r\s]+/', ' ', $input);

		// Replace synopsis to retain newlines.
		$input['synopsis'] = $synopsis;

		// Sanitize comma separated tags.
		if(isset($input['tags']) && $input['tags'] != '')
		{
			// Ensure tags are CSV, valid, and that values do not contain non-alphanumeric characters.
			$input['tags'] = strtolower(preg_replace("/[^A-Za-z0-9, ]/", '', $input['tags']));

			$tags_array = explode(",", $input['tags']);

			foreach($tags_array as $key => & $tag)
			{
				// Remove preceding whitespace from exploding the array.
				$tag = trim($tag);

				if($tag == "" || $tag == " ")
				{
					unset($tags_array[$key]);
				}
			}

			$tags_str = implode($tags_array, ",");
			$input['tags'] = $tags_str;
		}

		// Empty strings should be inserted into the database as NULL values.
		foreach($input as $key => $value)
		{
			if($value == "")
			{
				$input[$key] = NULL;
			}
		}

		// Replace the file information.
		$input['content_files'] = $files;

		// Save the sanitized data.
		$this->replace($input);
	}
}
