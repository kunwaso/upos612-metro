<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ProjectX\Http\Requests\Public\ContactSubmitRequest;
use Modules\ProjectX\Utils\SiteManagerUtil;

class PublicSiteController extends Controller
{
    protected SiteManagerUtil $siteManagerUtil;

    public function __construct(SiteManagerUtil $siteManagerUtil)
    {
        $this->siteManagerUtil = $siteManagerUtil;
    }

    public function about()
    {
        $data = array_merge($this->sharedData(), [
            'aboutHeading' => 'We Make Things Better',
            'aboutSubtitle' => 'Built with practical workflows and modern execution.',
            'aboutBody' => 'Save thousands to millions of bucks by using single tool for different amazing and great useful admin. This public profile uses the same ProjectX Metronic language to keep consistency across every page.',
        ]);

        return view('projectx::site_manager.pages.about', $data);
    }

    public function services()
    {
        $data = array_merge($this->sharedData(), [
            'servicesHeading' => 'How it Works',
            'servicesSubtitle' => 'Save thousands to millions of bucks by using single tool for different amazing and great useful admin',
            'services' => [
                [
                    'badge' => '1',
                    'title' => 'Jane Miller',
                    'description' => 'Save thousands to millions of bucks by using single tool for different amazing and great',
                    'image' => asset('modules/projectx/media/illustrations/sketchy-1/2.png'),
                ],
                [
                    'badge' => '2',
                    'title' => 'Setup Your App',
                    'description' => 'Save thousands to millions of bucks by using single tool for different amazing and great',
                    'image' => asset('modules/projectx/media/illustrations/sketchy-1/8.png'),
                ],
                [
                    'badge' => '3',
                    'title' => 'Enjoy Nautica App',
                    'description' => 'Save thousands to millions of bucks by using single tool for different amazing and great',
                    'image' => asset('modules/projectx/media/illustrations/sketchy-1/12.png'),
                ],
            ],
        ]);

        return view('projectx::site_manager.pages.services', $data);
    }

    public function blogIndex()
    {
        $posts = [
            [
                'title' => 'Bootstrap Admin Theme - How To Get Started Tutorial. Create customizable applications',
                'excerpt' => 'We have been focused on making the from v4 to v5 but we have also not been afraid to step away been focused on from v4 to v5 speaker approachable making focused.',
                'author' => 'Jane Miller',
                'publishedAt' => 'Apr 27 2021',
                'category' => 'BLOG',
                'avatar' => asset('modules/projectx/media/avatars/300-20.jpg'),
            ],
            [
                'title' => 'Angular Admin Theme - How To Get Started Tutorial.',
                'excerpt' => 'We have been focused on making the from v4 to v5 a but we have also not been afraid to step away.',
                'author' => 'Cris Morgan',
                'publishedAt' => 'Mar 14 2021',
                'category' => 'TUTORIALS',
                'avatar' => asset('modules/projectx/media/avatars/300-9.jpg'),
            ],
            [
                'title' => 'React Admin Theme - How To Get Started Tutorial. Create best applications',
                'excerpt' => 'We have been focused on making the from v4 to v5 but we have also not been afraid to step away been focused.',
                'author' => 'Bran Alvin',
                'publishedAt' => 'Feb 8 2021',
                'category' => 'CODE',
                'avatar' => asset('modules/projectx/media/avatars/300-19.jpg'),
            ],
        ];

        $data = array_merge($this->sharedData(), [
            'blogHeading' => 'Latest Articles, News & Updates',
            'blogFeature' => [
                'title' => 'Admin Panel - How To Get Started Tutorial. Create easy customizable applications',
                'excerpt' => 'We have been focused on making the from v4 to v5 but we have also not been afraid to step away been focused on from v4 to v5 speaker approachable making focused a but from a step away.',
                'author' => 'David Morgan',
                'publishedAt' => 'Apr 27 2021',
                'category' => 'TUTORIALS',
                'avatar' => asset('modules/projectx/media/avatars/300-9.jpg'),
                'videoUrl' => 'https://www.youtube.com/embed/TWdDZYNqlg4',
            ],
            'posts' => $posts,
        ]);

        return view('projectx::site_manager.pages.blog.index', $data);
    }

    public function contact()
    {
        $data = array_merge($this->sharedData(), [
            'contactHeading' => 'Send Us Email',
            'contactPhone' => '1 (833) 597-7538',
            'contactAddress' => 'Churchill-laan 16 II, 1052 CD, Amsterdam',
            'contactSocialLinks' => [
                ['icon' => asset('modules/projectx/media/svg/brand-logos/facebook-4.svg'), 'url' => '#'],
                ['icon' => asset('modules/projectx/media/svg/brand-logos/instagram-2-1.svg'), 'url' => '#'],
                ['icon' => asset('modules/projectx/media/svg/brand-logos/github.svg'), 'url' => '#'],
                ['icon' => asset('modules/projectx/media/svg/brand-logos/behance.svg'), 'url' => '#'],
                ['icon' => asset('modules/projectx/media/svg/brand-logos/pinterest-p.svg'), 'url' => '#'],
                ['icon' => asset('modules/projectx/media/svg/brand-logos/twitter.svg'), 'url' => '#'],
                ['icon' => asset('modules/projectx/media/svg/brand-logos/dribbble-icon-1.svg'), 'url' => '#'],
            ],
        ]);

        return view('projectx::site_manager.pages.contact', $data);
    }

    public function contactSubmit(ContactSubmitRequest $request)
    {
        $request->validated();

        return redirect()
            ->route('public.contact')
            ->with('status', [
                'success' => true,
                'msg' => 'Thank you. Your message has been received.',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedData(): array
    {
        $data = $this->siteManagerUtil->getWelcomeViewData(null);

        if (empty($data['navItems']) || ! is_array($data['navItems'])) {
            $data['navItems'] = [
                ['label' => 'Home', 'url' => url('/')],
                ['label' => 'About', 'url' => route('public.about')],
                ['label' => 'Services', 'url' => route('public.services')],
                ['label' => 'Blog', 'url' => route('public.blog.index')],
                ['label' => 'Contact', 'url' => route('public.contact')],
            ];
        }

        return $data;
    }
}
