<ul class="language_bar_chooser">
    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
        @if($localeCode == LaravelLocalization::getCurrentLanguage())
            <li class="active">{{{ !empty($abbr) ? $localeCode : $properties['native'] }}}</li>
        @else
            <li><a rel="alternate" hreflang="{{$localeCode}}" href="{{LaravelLocalization::getLocalizedURL($localeCode)}}">{{{ !empty($abbr) ? $localeCode : $properties['native'] }}}</a></li>
        @endif
    @endforeach
</ul>
