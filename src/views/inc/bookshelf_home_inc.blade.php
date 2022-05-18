{{App()->setLocale(Session::get('userLanguage'))}}
@php
    //Todo move to controller
    $records = Yarm\Bookshelf\Http\Controllers\BookshelfController::getBookshelfData('Sidebar', 5);
@endphp
<div class="card bookshelf sidebar">
    <div class="card-header">
        <h4>@lang('Bookshelf recently added') <a title="Info" data-placement="top" data-toggle="popover"
                                                 data-trigger="hover"
                                                 id="notePopoverOptions"
                                                 data-content="@lang('Recently added files, click the icon in the corner to manage your bookshelf.')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a></h4>

        <a href="{{route('bookshelfForm')}}">
            <li style="float: right; color: seagreen" class="fa fa-external-link  fa-lg"></li>
        </a>
    </div>
    <div class="card-body" style="justify-content: center">
        {{--        //TODO refactor as  function???--}}
        @if(!empty($records['records']))
            <div class="bookshelf-sidebar-content">
                @include('bookshelf::inc.bookshelf_data_sidebar_inc', array('rows'  => $records['rows']))
            </div>
        @else
            <div class="bookshelf-sidebar-content">
                <h3>{{$records['message']}}</h3>
            </div>
        @endif
    </div>
</div>


