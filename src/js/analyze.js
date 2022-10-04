;(function ($) {
    $(document).ready(function () {
        /* Setup for ajax*/
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.btn-to-loader .btn').on('click', function () {
            $('.btn-to-loader').html(" <div class=\"spinner-grow text-primary m-auto\" role=\"status\">\n" +
                "                            <span class=\"sr-only\">Loading...</span>\n" +
                "                        </div> ");
            $('.btn-to-loader').parents('form').submit();
        });
        $('.btn-for-loader .btn').on('click', function () {
            $('.load-loader').html(" <div class=\"spinner-grow text-primary float-right m-auto\" role=\"status\">\n" +
                "                            <span class=\"sr-only\">Loading...</span>\n" +
                "                        </div> ");
            $('.btn-to-loader-r').parents('form').submit();
        });
    });

    $(document).on('click', '.add-frame', function () {
        //Set values from chosen file to variables
        var file = $('.selectFile').val().split('#')[0];
        var fileName = $('.selectFile option:selected').text();
        var tool = $('.selectTool').val();
        var extension = file.substr(file.length - 3).toUpperCase();


        //Set path for downloaded files
        if ($('.selectFile').val().split('#')[1] !== undefined) {
            file = $('.selectFile').val().split('#')[1];
        }

        //Draw frame with Voyant tool inside it
        var frame = "<div class='tool-frame col-12'>" +
            "<div class='card bookshelf mt-3'>" +
            "<div class='card-header'>" +
            "<div class='row'>" +

            "<div class='col-1'>" +
            "<a href='javascript:void(0)' data-url=" + YARM.voyant_url + "/tool/" + tool + "/?inputFormat=" + extension + "&input=http://localhost:8888/data_dlbt/YARMDBUploads/" + file + " id='open-new-tab-frame'>" +
            "<i class='fa fa-external-link fa-lg' style='color: green'></i>" +
            "</a>" +
            "</div>" +

            "<div class='col-10'>" +
            "<h5>" + fileName + " </h5>" +
            "</div>" +

            "<div class='col-1'>" +
            "<p>" +
            "<a href='javascript:void(0)' id='delete-frame'>" +
            "<i class='fa fa-trash fa-lg' style='color: red; ; font-size: 1.5em'></i>" +
            "</a><br>" +

            "</p>" +
            "</div>" +
            "</div>" +
            "</div>" +

            "<div class='card-body'>" +
            "<iframe class='col-12' style='width: 700px; height: 500px; align-content: center;' src=" + YARM.voyant_url + "/tool/" + tool + "/?inputFormat=" + extension + "&input=http://localhost:8888/data_dlbt/YARMDBUploads/" + file + "></iframe>" +
            "</div>" +

            "</div>";

        $('.toolFrame').append(frame);
    });

    //Delete voyant frame
    $(document).on('click', '#delete-frame', function (e) {
        e.preventDefault();
        var button = $(this);
        var toolFrame = button.parents('.tool-frame');

        toolFrame.remove();

    });
    //Open voyant frame in new tab
    $(document).on('click', '#open-new-tab-frame', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        window.open(url, 'Voyant');
    });
    $('[data-toggle="tooltip"]').tooltip({
        trigger : 'hover'
    })

}(jQuery));
