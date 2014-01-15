<ul class="{{ LaravelLocalization::getLanguageBarClassName() }}">
	@foreach($languages as $key => $lang)
		@if($key == $active)
			@if(LaravelLocalization::getPrintCurrentLanguage())
				<li class="active">{{$lang}}</li>
			@endif
		@else
			<li><a rel="alternate" hreflang="{{$key}}" href="{{$urls[$key]}}">{{$lang}}</a></li>
		@endif
	@endforeach
</ul>
