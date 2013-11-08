<ul class="{{ Config::get('laravel-localization::languageBarClass') }}">
	@foreach($languages as $key => $lang)
		@if($key == $active) 
			<li class="active">{{$lang}}</li>
		@else
			<li><a rel="alternate" href="{{$urls[$key]}}" hreflang="{{$key}}">{{$lang}}</a></li>
		@endif
	@endforeach
</ul>
