<tbody>
@forelse($rows as $row)
<tr>
    @foreach($row as $cell)
        <td>{{ $cell }}</td>
    @endforeach
</tr>
@empty
<tr><td colspan="{{ count($columns) }}" style="text-align:center;">No data found</td></tr>
@endforelse
</tbody>