
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($pages as $page)
        <url>
            <loc>{{ $page['url'] }}</loc>
            <priority>{{ $page['priority'] }}</priority>
        </url>
    @endforeach

    @foreach ($profiles as $profile)
        <url>
            <loc>{{ route('member-profiles.show', ['profile' => $profile->username]) }}</loc>
            <lastmod>{{ $profile->updated_at->toAtomString() }}</lastmod>
            <priority>0.7</priority>
        </url>
    @endforeach
</urlset>
