@if(\Auth::guest())
<p style="padding: 10px">
Please <a href="/login">login</a> to see your watchlists.
</p>
{{die()}}
@endif