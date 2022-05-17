@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

@section('content')
    <div class="card">
        <div class="card-header">
            {{--Todo popover text--}}
            <h3>@lang('Bookshelf
')
                <a title="Info" data-placement="top" data-toggle="popover"
                   data-trigger="hover"
                   data-content="@lang('Manage your bookshelf here. You can download and convert your files if possible.')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a>
            </h3>
        </div>
        <div class="card-body">
            @if(!empty($message))
                <div class="bookshelf-sidebar-content">
                    <h3>{{$message}}</h3>
                </div>
            @endif
            @if(isset($rows))
                <div class="card mb-3">
                    <div class="card-body p-2">
                        <h5 class="card-title">@lang('Filter results by:')
                            {!! Form::label('search',  __('Show only results containing:'), ['class' => 'sr-only font-weight-bold']); !!}</h5>
                            {!! Form::text('search', '', ['class' => 'form-control', 'placeholder' => __('Keyword(s)')]) !!}
                    </div>
                </div>
            @endif

            <div class="bookshelfContent">
                @include('dlbt.bookshelf.inc.bookshelf_data_inc')
            </div>

            <input type="hidden" name="hidden_page" id="hidden_page" value="1"/>
            <input type="hidden" name="type" id="type" value="shelf_book"/>
            <input type="hidden" name="view" id="view" value="bookshelf"/>

            <div class="modal fade" id="modalDeleteBookshelf" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="bookShelfModalDelete"></h4>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    id="btn-delete-dismiss-bookshelf">@lang('Close')</button>
                            <button type="button" class="btn btn-danger" id="btn-delete-bookshelf"
                                    value="delete-item"
                                    data-table=""
                                    data-id="">@lang('Delete')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
