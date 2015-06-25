@extends('main')

@section('styles')
<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.6.0/pure-min.css">
@endsection

@section('title', 'Edit Book')

@section('content')
<section class="container">
    <div class="header">
            <h3>Book Detail
            {!! Form::open(['url' => 'book/delete', 'id' => 'form_delete_book', 'class' => 'form__delete-button']) !!}
                {!! Form::submit('Delete Book', ['id' => 'delete_book', 'class' => 'pure-button button--delete'] ) !!}
                {!! Form::hidden('bid', $book['bid'], NULL) !!}
            {!! Form::close() !!}
        <div class="separator"> </div>
    </div>
    {!! Form::model($book, ['id' => 'form_edit_book', 'url' => 'book/edit/' . $book['bid'], 'enctype' => 'multipart/form-data', 'class' => 'pure-form pure-form-aligned']) !!}
        {!! Form::hidden('bid', $book['bid'], NULL) !!}
    <div class="image">
        @if($book['has_cover'])
            <?php $cover_image_path = 'uploads/' . $book['uid'] . '/' . $book['file_name'] . '.jpg'; ?>
            <img id="book_cover_image" src="{{ asset($cover_image_path) }}" alt="book cover" />
            {{-- Display button to remove cover. --}}
            <p>
                {!! Form::button('Remove Cover', ['name' => 'remove_cover', 'class' => 'pure-button button--replace']) !!}
            </p>
            {{-- Hide form elements for uploading a cover until the user clicks the 'remove cover' option. --}}
            <div class="hide">
        @else
            <img id="book_cover_image" src="{{ asset('img/default_covers/default_cover.jpg') }}" alt="book cover" />
            <div>
        @endif
    			{!! Form::label('form_upload_cover', 'Upload Cover (JPG):', ['class' => 'form__label']) !!}
    			<input type="text" id="form_upload_text_cover" class="form__upload--text" placeholder="" disabled="disabled" />
    			<div class="form__upload pure-button button--secondary">
    				<span>Choose File</span>
    				{!! Form::file('image_file', ['id' => 'form_upload_cover', 'class' => 'form__upload--button', 'accept' => 'image/jpg, image/jpeg']) !!}
    			</div>
    		</div>
        <?php $error = $errors->first('image_file'); ?>
        @if(isset($error) && $error != "") <p>{{ $error }}</p> @endif

        <br />

        <p>Social Media Icons</p>

        <div>
            <p>View Analytics</p>
            <p>See Download Activity</p>
        </div>
    </div> {{-- end image section --}}

    <div class="information">
        <div class="pure-control-group">
            {!! Form::label('title', 'Title:', ['class' => 'form__label--required']) !!}
            {!! Form::text('title', NULL, ['class' => 'form__input'] ) !!}
            {!! $errors->first('title', '<span class="error">:message</span>') !!}
        </div>
        <div class="pure-control-group">
            {!! Form::label('co_authors', 'Co-authors:', ['class' => 'form__label']) !!}
            {!! Form::text('co_authors', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('co_authors') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('genre', 'Genre:', ['class' => 'form__label--required']) !!}
            {!! Form::select('genre', $list, ' ', ['class' => 'form__input'] ) !!}
            {{ $errors->first('genre') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('series_name', 'Series Name:', ['class' => 'form__label']) !!}
            {!! Form::text('series_name', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('series_name') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('series_number', 'Series Number:', ['class' => 'form__label']) !!}
            {!! Form::text('series_number', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('series_number') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('language', 'Language:', ['class' => 'form__label']) !!}
            {!! Form::text('language', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('language') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('isbn', 'ISBN:', ['class' => 'form__label']) !!}
            {!! Form::text('isbn', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('isbn') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('word_count', 'Word Count:', ['class' => 'form__label']) !!}
            {!! Form::text('word_count', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('word_count') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('page_count', 'Page Count:', ['class' => 'form__label']) !!}
            {!! Form::text('page_count', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('page_count') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('synopsis', 'Synopsis:', ['class' => 'form__label']) !!}
            {!! Form::textarea('synopsis', NULL, ['class' => 'form__input'] ) !!}
            {{ $errors->first('synopsis') }}
        </div>

        <div class="pure-control-group">
            {!! Form::label('tags', 'Tags:', ['class' => 'form__label']) !!}
            @if(isset($tags))
                {!! Form::text('tags', $tags, ['class' => 'form__input', 'placeholder' => 'separate tags by comma...'] ) !!}
            @else
                {!! Form::text('tags', NULL, ['class' => 'form__input', 'placeholder' => 'separate tags by comma...'] ) !!}
            @endif
            {{ $errors->first('tags') }}
        </div>

        @if($errors->first('files'))
            <div class="pure-controls">
                {{ $errors->first('files') }}
            </div>
        @endif

        <?php $extensions = array("pdf", "epub", "mobi", "txt"); ?>

        @foreach($extensions as $key => $extension)
            {{-- Check if the user has already uploaded one of these files. If so, hide the form elements and show the 'remove' button. --}}
            <?php
                $has_format = false;
                foreach($formats as $format)
                {
                    if($extension == $format['format'])
                    {
                        $has_format = true;
                        break;
                    }
                }
            ?>
            @if($has_format)
                {{-- Display button to remove file and hide upload options. --}}
                <div class="pure-controls">
                    {!! Form::button('Remove ' . strtoupper($extension) . " File", ['name' => 'remove_content_file[]', 'id' => 'remove_content_file_' . $extension, 'class' => 'pure-button button--replace']) !!}
                </div>
                <div class="hide pure-control-group">
            @else
                {{-- Otherwise, display upload options as usual. --}}
                <div class="pure-control-group">
            @endif
                    {!! Form::label('form_upload_' . $extension, 'Upload ' . strtoupper($extension) . ':', ['class' => 'form__label']) !!}
                    <input id="form_upload_text_{{ $extension }}" class="form__upload--text pure-input-1-4" type="text" placeholder="" disabled="disabled" />
                    <div class="form__upload pure-button button--secondary">
                        <span>Choose File</span>
                        {!! Form::file('content_files[]', ['id' => 'form_upload_file_' . $extension, 'class' => 'form__upload--button', 'accept' => '.' . $extension]) !!}
                    </div>
                    {{ $errors->first('content_files.' . $key) }}
                </div>

        @endforeach

        <div class="pure-controls">
            {{-- Terms were already accepted when the user first published the book. --}}
            {!! Form::checkbox('accept_terms', NULL, NULL, ['style' => 'display:none;', 'checked' => 'checked'] ) !!}

            <p>Captcha</p>

            {!! Form::submit('Save Edits', ['class' => 'pure-button pure-button-primary'] ) !!}
        </div>
    {!! Form::close() !!}
    </div> {{-- end information section--}}
</section>
@endsection

@section('register_scripts')
<script type="text/javascript" src="{{ URL::asset('js/r_book_edit.js') }}"></script>
@endsection
