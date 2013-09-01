<ul class="laravel_language_chooser">
	@foreach($languages as $key => $lang)
		@if($key == $active) 
			<li class="active">{{$lang}}</li>
		@else
			<li><a href="{{$urls[$key]}}">{{$lang}}</a></li>
		@endif
	@endforeach
</ul>