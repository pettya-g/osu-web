<?php

/**
 *    Copyright 2015-2017 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App;
use App\Libraries\CurrentStats;
use App\Models\Beatmapset;
use App\Models\Build;
use App\Models\Changelog;
use App\Models\Forum\Post;
use App\Models\News;
use Auth;
use Request;
use View;

class HomeController extends Controller
{
    protected $section = 'home';

    public function bbcodePreview()
    {
        $post = new Post(['post_text' => Request::input('text')]);

        return $post->bodyHTML;
    }

    public function getChangelog()
    {
        $streamId = get_int(Request::input('stream_id'));
        $build = Request::input('build');

        if ($streamId && $build) {
            // in this case ignore the build and show page for the stream id
            $build = null;
        } elseif (!$streamId && !$build) {
            $streamId = config('osu.changelog.featured_stream');
        }

        $streamIds = implode(',', config('osu.changelog.update_streams'));

        $builds = Build::orderBy('date', 'desc')
            ->take($build ? 1 : config('osu.changelog.build_count'));

        if ($streamId) {
            $builds->where('stream_id', $streamId);
        } elseif ($build) {
            $builds->where('version', $build);
        }

        $changelogs = Changelog::default()->whereIn('build', $builds->pluck('version'))
            ->with(['gameBuild', 'user'])
            ->orderBy('date', 'desc')
            ->get()
            ->sortByDesc('major')
            ->groupBy(function ($item, $key) {
                return $item->gameBuild->date;
            });

        $streams = Build::latestByStream()->with('updateStream')->get();
        $featuredStream = null;

        foreach ($streams as $index => $stream) {
            if ($stream->stream_id === config('osu.changelog.featured_stream')) {
                $featuredStream = $stream;
                unset($streams[$index]);
                break;
            }
        }

        return view('home.changelog', compact('changelogs', 'streams', 'featuredStream', 'streamId'));
    }

    public function getDownload()
    {
        return view('home.download');
    }

    public function getIcons()
    {
        return view('home.icons')
        ->with('icons', [
            'osu',
            'mode-osu',
            'mode-mania',
            'mode-fruits',
            'mode-taiko',
            'social-patreon',
        ]);
    }

    public function index()
    {
        $host = Request::getHttpHost();
        $subdomain = substr($host, 0, strpos($host, '.'));

        if ($subdomain === 'store') {
            return ujs_redirect(route('store.products.index'));
        }

        if (Auth::check()) {
            $news = News::all();
            $newBeatmapsets = Beatmapset::latestRankedOrApproved();
            $popularBeatmapsetsPlaycount = Beatmapset::mostPlayedToday();
            $popularBeatmapsetIds = array_keys($popularBeatmapsetsPlaycount);
            $popularBeatmapsets = Beatmapset::whereIn('beatmapset_id', $popularBeatmapsetIds)
                ->orderByField('beatmapset_id', $popularBeatmapsetIds)
                ->get();

            return view('home.user', compact(
                'newBeatmapsets',
                'news',
                'popularBeatmapsets',
                'popularBeatmapsetsPlaycount'
            ));
        } else {
            return view('home.landing', ['stats' => new CurrentStats()]);
        }
    }

    public function setLocale()
    {
        $newLocale = get_valid_locale(Request::input('locale'));
        App::setLocale($newLocale);

        if (Auth::check()) {
            Auth::user()->update([
                'user_lang' => $newLocale,
            ]);
        }

        return js_view('layout.ujs-reload')
            ->withCookie(cookie()->forever('locale', $newLocale));
    }

    public function supportTheGame()
    {
        return view('home.support-the-game')
        ->with('data', [
            // why support's blocks
            'blocks' => [
                // localization's name => icon
                'dev' => 'user',
                'time' => 'clock-o',
                'ads' => 'thumbs-up',
                'goodies' => 'star',
            ],

            // supporter's perks
            'perks' => [
                // localization's name => icon
                'osu_direct' => 'search',
                'auto_downloads' => 'cloud-download',
                'upload_more' => 'cloud-upload',
                'early_access' => 'flask',
                'customisation' => 'picture-o',
                'beatmap_filters' => 'filter',
                'yellow_fellow' => 'fire',
                'speedy_downloads' => 'dashboard',
                'change_username' => 'magic',
                'skinnables' => 'paint-brush',
                'feature_votes' => 'thumbs-up',
                'sort_options' => 'trophy',
                'feel_special' => 'heart',
                'more_to_come' => 'gift',
            ],
        ]);
    }
}
