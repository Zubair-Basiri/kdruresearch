@foreach($rows as $row)
<tr>
    @foreach($row as $cell)
        <td>{{ $cell }}</td>
    @endforeach
</tr>
@endforeach