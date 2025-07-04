<ul class="nav nav-tabs mb-4 topTap breadcrumb-nav" role="tablist">
    <button class="breadcrumb-nav-close"><i class="las la-times"></i></button>
    <li class="nav-item {{ menuActive('admin.gamesetting.index') }}" role="presentation">
        <a href="{{ route('admin.gamesetting.index') }}" class="nav-link text-dark" type="button">
            <i class="las la-clock"></i> @lang('Game Setting')
        </a>
    </li>
    <li class="nav-item {{ menuActive('admin.game.index') }}" role="presentation">
        <a href="{{ route('admin.game.index') }}" class="nav-link text-dark" type="button">
            <i class="las la-list"></i> @lang('Manage Game')
        </a>
    </li>
</ul>
