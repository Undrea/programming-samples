<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Request;
use Redirect;
use Auth;
use DB;
use Input;
use App\User;
use App\Book;
use App\Tag;
use App\BookTag;
use App\Format;

class BookController extends Controller {

	// 	...
	// 	Class contents abbreviated for demonstration purposes.
	//	...
	//	...
	//	...

	/**
	 * Show the form for creating a new resource (publish a book).
	 *
	 * @return Response
	 */
	public function create()
	{
		if(Auth::check())
		{
			// If user is logged in, show the publish book form.
			return view('book/create');
		}
		else
		{
			// If the user is not logged in, redirect to login screen.
			return redirect('login');
		}
	}

	/**
	 * Display the specified resource (show the book detail page).
	 *
	 * @param  int  $bid - ID of the book to display.
	 * @return Response
	 */
	public function show($bid)
	{
		// As long as the BID is set and valid, query DB and send the information to the view.
		if(isset($bid))
		{
			$book = Book::where('bid', '=', $bid)
						->select([
							'title',
							'bid',
							'uid',
							'gid',
							'co_authors',
							'series_name',
							'series_number',
							'synopsis',
							'word_count',
							'page_count',
							'language',
							'isbn',
							'created_at',
							'updated_at',
							'has_cover',
							'views',
							'downloads',
							'file_name'
							])->first();

			$author = User::where('uid', '=', $book->uid)
						->select([
							'uid',
							'name'
						])->first();

			$formats = Format::where('bid', '=', $bid)
						->select([
							'format',
							'file_size'
						])->get();

			$tags = DB::table('tags')
						->join('books_tags', 'tags.tid', '=', 'books_tags.tid')
						->where('books_tags.bid', '=', $book->bid)
						->select([
							'tags.tag'
							])->get();

			$tags_str = NULL;
			if(isset($tags))
			{
				$tags_str = "";
				foreach($tags as $key => $tag)
				{
					if($key == 0)
						$tags_str .= $tag->tag;
					else
						$tags_str = $tags_str . ', ' . $tag->tag;
				}
			}

			// Increment number of views for this book.
			$book->views = $book->views + 1;
			$book->save();

			return view('book/show', ['book' => $book, 'author' => $author, 'tags' => $tags_str, 'formats' => $formats]);
		}
		else
		{
			return redirect('browse');
		}
	}

	/**
	 * Show the form for editing the specified resource (edit a book).
	 *
	 * @param  int  $bid - ID of the book to display.
	 * @return Response
	 */
	public function edit($bid)
	{
		// Get the book information.
		$book = Book::where('bid', '=', $bid)
					->select([
						'title',
						'bid',
						'uid',
						'gid',
						'co_authors',
						'series_name',
						'series_number',
						'synopsis',
						'word_count',
						'page_count',
						'language',
						'isbn',
						'created_at',
						'updated_at',
						'has_cover',
						'views',
						'downloads',
						'file_name'
						])->first();

		// If user is logged in and owns the book, display the edit form.
		if(Auth::check() && Auth::user()->uid == $book['uid'])
		{
			$formats = Format::where('bid', '=', $bid)
						->select([
							'format',
							'file_size'
						])->get();

			$tags = DB::table('tags')
						->join('books_tags', 'tags.tid', '=', 'books_tags.tid')
						->where('books_tags.bid', '=', $book->bid)
						->select([
							'tags.tag'
							])->get();

			// Generate tag CSVs.
			$tags_str = NULL;
			if(isset($tags))
			{
				$tags_str = "";
				foreach($tags as $key => $tag)
				{
					if($key == 0)
						$tags_str .= $tag->tag;
					else
						$tags_str = $tags_str . ', ' . $tag->tag;
				}
			}

			return view('book/edit', ['book' => $book, 'tags' => $tags_str, 'formats' => $formats ]);
		}
		else
		{
			return redirect('book/' . $bid);
		}
	}

	/**
	 * Update the specified resource in storage (save book edits).
	 *
	 * @param EditBookRequest $request - post request which authorizes, sanitizes, and validates form data,
	 *									 redirecting to previous page with errors if validation fails.
	 * @return Response
	 */
	public function update($bid, Requests\EditBookRequest $request)
	{
		$input = $request->all();

		//--------------------------------------------------------------------------
		// 	Perform requested file manipulation (adding and deleting).
		//--------------------------------------------------------------------------

		// Fetch the file_name given to these books.
		$book = Book::find($bid);

		$file_name = $book['file_name'];
		$has_cover = $book['has_cover'];

		// Get the user's private directory for file storage.
		$file_destination = BookController::UPLOAD_STORAGE_PATH . '/' . Auth::user()->uid;

		// Delete any files as requested.
		foreach(BookController::FORMATS as $key => $format)
		{
			if(isset($input['remove_' . $format]))
			{
				$file_path 	= "$file_destination/$file_name.$format";
				BookController::removeFile($file_path);

				// Update the formats table.
				$match = ['bid' => $book['bid'], 'format' => $format];
				$format = Format::where($match)->delete();
			}
		}

		// Delete cover image if requested.
		if(isset($request['remove_cover']))
		{
			// Delete cover as well as thumbnail.
			$file_path 	= $file_destination . '/' . $file_name . '.jpg';
			BookController::removeFile($file_path);

			$file_path 	= $file_destination . '/' . $file_name . '_thumb.jpg';
			BookController::removeFile($file_path);

			// Save to update the book record.
			$has_cover = false;
		}

		// Get all files.
		$files = Input::file();

		// Save the provided book content files to the user's upload directory.
		$content_files = $files['content_files'];
		foreach($content_files as $key => $file)
		{
			if(isset($file))
			{
				$file_destination = BookController::UPLOAD_STORAGE_PATH . '/' . Auth::user()->uid;
				$file->move($file_destination, $file_name . '.' . BookController::FORMATS[$key]);
			}
		}

		// If a cover image was supplied, save it and generate a thumbnail in the user's upload directory.
		if(isset($files['image_file']))
		{
			$cover_file = $files['image_file'];

			$file_destination = BookController::UPLOAD_STORAGE_PATH . '/' . Auth::user()->uid;
			$cover_file->move($file_destination, $file_name . '.jpg');

			// Generate thumbnail.
			BookController::makeCoverThumbnail($file_destination . "/", $file_name);
			$has_cover = true;
		}

		//--------------------------------------------------------------------------
		// 	Update the book information in the database
		//--------------------------------------------------------------------------

		$book = Book::find($bid);

		$book->uid 				= Auth::user()->uid;
		$book->gid 				= 28;
		$book->title 			= $input['title'];
		$book->co_authors 		= $input['co_authors'];
		$book->series_name 		= $input["series_name"];
		$book->series_number 	= $input["series_number"];
		$book->synopsis 		= $input["synopsis"];
		$book->word_count 		= $input["word_count"];
		$book->page_count 		= $input["page_count"];
		$book->language 		= $input["language"];
		$book->isbn 			= $input["isbn"];
		$book->has_cover 		= $has_cover;

		$book->save();

		// Update the formats table for each content file that is associated with this book.
		foreach($content_files as $key => $file)
		{
			if(isset($file))
			{
				// Save in formats table...
				$format = new Format();

				$format->bid 		= $book->bid;
				$format->file_size 	= $file->getClientSize();	// Gets byte value.
				$format->format 	= BookController::FORMATS[$key];

				$format->save();
			}
		}

		// Check if a cover image was uploaded.
		if(isset($files['image_file']))
		{
			$book->has_cover 		= true;
		}

		// Update the tags table for each book tag.
		BookController::clearTags($book->bid);
		if($request['tags'] != NULL)
		{
			$tags_array = explode(",", $request['tags']);

			// All tags were cleared with clearTags... now update the book with its new tags.
			BookController::addTags($book->bid, $tags_array);

		}

		// Cleanup any tags no longer references by a book -=- Move to a maintenance script
		BookController::clearUnpairedTags();

		return redirect('book/' . $book->bid);
	}

	/**
	* Delete the specified resource (delete a book).
	*
	* @param  Post Request
	* @return Response
	*/
	public function destroy()
	{
		$method = Request::method();

		if (Request::isMethod('post'))
		{
			// Get the POST data.
			$data = Input::all();
			$bid = intval($data['bid']);

			// If user is logged in and owns the book, delete it and the associated files.
			if($book = Book::find($bid))
			{
				$book->first();
				if(Auth::check() && Auth::user()->uid == $book['uid'])
				{
					// Clear all files for that book.
					$author_directory_path = BookController::UPLOAD_STORAGE_PATH . '/' . $book->uid . '/';
					BookController::deleteBookFiles($author_directory_path, $book->file_name);

					// Delete records associated with BID. Foreign keys deal with this.
					$book->delete();

					BookController::clearUnpairedTags();
				}
				else
				{
					return redirect('login');
				}
			}
		}

		return redirect('user');
	}

	/**
	* Helper - checks if an image file has valid dimensions.
	*
	* @param  File $file - an image file.
	* @return bool
	*/
	public static function isValidCoverDimensions($file)
	{
		// Get image file dimensions.
		list($width, $height) = getimagesize($file);

		return (
			($width 	<= BookController::COVER_MAX_WIDTH) 	&&
			($width 	>= BookController::COVER_MIN_WIDTH) 	&&
			($height 	<= BookController::COVER_MAX_HEIGHT) 	&&
			($height 	>= BookController::COVER_MIN_HEIGHT)
		);
	}

	/**
	* Helper - generate a cover image thumbnail.
	*
	* @param  string $user_directory - directory containing a user's uploads.
	* @param  string $cover_file_name - name of the cover image for which a thumbnail will be generated.
	* @return true or false
	*/
	private static function makeCoverThumbnail($user_directory, $cover_file_name)
	{
		// Get image location.
		$image_path = $user_directory . "/" . $cover_file_name . ".jpg";

		// load image and get image size.
		$img 	= imagecreatefromjpeg($image_path);
		$width 	= imagesx( $img );
		$height = imagesy( $img );

		// Calculate thumbnail size, constraining proportionates.
		$new_width = BookController::THUMB_WIDTH;
		$new_height = floor( $height * ( BookController::THUMB_WIDTH / $width ) );

		// Also check max thumbnail height as well as width.
		if($new_height > BookController::THUMB_HEIGHT)
		{
			$new_height = BookController::THUMB_HEIGHT;
			$new_width = floor( $width * ( BookController::THUMB_HEIGHT / $height ) );
		}

		// Create a new temporary image.
		$tmp_img = imagecreatetruecolor( $new_width, $new_height );

		// Copy and resize old image into new image.
		imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

		// Save thumbnail into a file.
		$new_name = $user_directory . $cover_file_name . "_thumb.jpg";
		imagejpeg($tmp_img, $new_name);
	}

	/**
	* Helper - removes a file.
	*
	* @param  string $file_path - path to the file to be deleted.
	*/
	private static function removeFile($file_path)
	{
		if(is_file($file_path) && file_exists($file_path))
		{
			// Delete the file (must not be open in some application).
			if(!@unlink($file_path))
			{
				//
			}
		}
	}

	/**
	* Helper - delete all files (content and cover image) related to a book.
	*
	* @param  string $author_directory_path - path to the user's directory.
	* @param  string $file_name - unique name prefix to all files used for a book.
	*/
	private static function deleteBookFiles($author_directory_path, $file_name)
	{
		//	Remove all files in $author_directory_path prefixed by the name $file_name.
		$author_directory_files = glob($author_directory_path . $file_name . "*");

		foreach($author_directory_files as $file)
		{
			if(!is_dir($file))
			{
				if(!unlink($file))
				{
					//
				}
			}
		}
	}

	/**
	* Helper - adds specified tags to a book.
	*
	* @param  int $bid - ID of the book.
	* @param  string[] $tags_array - array of tags belonging book $bid.
	* @param  string $file_name - unique name prefix to all files used for a book.
	*/
	private static function addTags($bid, $tags_array)
	{
		foreach($tags_array as $tag)
		{
			$db_tag = Tag::where('tag', '=', $tag)->first();

			// If the tag doesn't exist, add it to the tags table.
			if(!isset($db_tag))
			{
				$new_tag = new Tag;
				$new_tag->tag = $tag;
				$new_tag->save();

				// Get the TID of the newly inserted tag.
				$tid_link = $new_tag->tid;
			}
			else
			{
				// Get the TID of the pre-existing tag.
				$tid_link = $db_tag->tid;
			}

			// Check if there is already a link to this tag.
			$match = ['tid' => $tid_link, 'bid' => $bid];
			$db_bookstags = BookTag::where($match)->first();

			if(!isset($db_bookstags))
			{
				// Link the tag to this book.
				$books_tags = new BookTag();
				$books_tags->bid = $bid;
				$books_tags->tid = $tid_link;
				$books_tags->save();
			}
		}
	}

	/**
	* Helper - clear all tags having to do with a book.
	*
	* @param  int $bid - ID of the book.
	* @param  string $file_name - unique name prefix to all files used for a book.
	*/
	private static function clearTags($bid)
	{
		// Delete all tag links associated with this book from books_tags.
		$affected_rows = BookTag::where('bid', '=', $bid)->delete();

		// MySQL:
		// SELECT * FROM books_tags bt
		// 	RIGHT JOIN tags t
		// 		ON bt.tid = t.tid
		// 			WHERE bt.bid IS NULL
	}

	/**
	* Helper - deletes all tags that are not related to any book.
	*
	*/
	private static function clearUnpairedTags()
	{
		// Remove any tags in the tags table that no longer match to any book.
		$affected_rows = DB::table('tags')
							->leftJoin('books_tags', 'tags.tid', '=', 'books_tags.tid')
							->whereNull('books_tags.bid')
							->delete();

	}
}
