<ul class="language_bar_chooser">
	@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
		@if($localeCode == LaravelLocalization::getCurrentLocale())
			<li class="active">
				{{{ !empty($abbr) ? $localeCode : $properties['native'] }}}
			</li>
		@elseif($url = LaravelLocalization::getLocalizedURL($localeCode))
			<li>
				<a rel="alternate" hreflang="{{$localeCode}}" href="{{$url}}">
					{{{ !empty($abbr) ? $localeCode : $properties['native'] }}}
				</a>
			</li>
		@endif
	@endforeach
</ul>
