<header class="masthead mb-auto">
    <div class="inner">
        <h3 class="masthead-brand"><a href="{{ home_url('/') }}"><img src="{{$data['theme']['logo']['default']}}" class="logo default" alt="{{$data['site']['name']}} Home" /></a></h3>
        <nav class="nav nav-masthead justify-content-center">
            @if (is_user_logged_in())
                <a href="{{ home_url('/wp-login.php?action=logout') }}" class="nav-link active">Sign Out</a>
            @else
                <a href="{{ home_url('/sign-in') }}" class="nav-link active">Sign In</a>
            @endif
            @if (has_nav_menu('primary_navigation'))
                {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav']) !!}
            @endif
        </nav>
    </div>
</header>