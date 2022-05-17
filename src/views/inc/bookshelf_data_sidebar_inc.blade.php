@if (isset($rows))
    <ul>
        @foreach ($rows as $ref)
            <li>
                @include('dlbt.styles.format_as_' . App\Models\Style::getNameStyle())
            </li>
        @endforeach
    </ul>
@endif


