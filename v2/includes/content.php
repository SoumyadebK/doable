<?php
/**
 * Default site content (mirrors the live site) + load/merge/save from the DB.
 * The admin Content editor overrides any of these values; until then the
 * defaults render so the site looks identical out of the box.
 */
require_once __DIR__ . '/db.php';

function default_content(): array {
    return [
        'general' => [
            'companyName' => 'Doable LLC',
            'footerTagline' => 'Enterprise-level software designed for private and class-based businesses. Reclaim your time and focus on what you love.',
        ],
        'hero' => [
            'badge' => 'The Future of Business Management',
            'titleLine1' => 'Run Your Business',
            'titleLine2' => '',
            'titleHighlight' => 'Effortlessly',
            'subheadline' => 'Enterprise-grade software designed for private and class-based businesses.',
            'subheadline2' => 'Scheduling. Payments. Marketing. All in one beautiful platform.',
            'primaryCta' => 'Start 30-Day Free Trial',
            'secondaryCta' => 'Watch Demo',
            'trustIndicators' => ['30-day free trial', 'Setup in 5 minutes'],
        ],
        'features' => [
            'badge' => 'Powerful Features',
            'title' => 'Everything you need,',
            'titleHighlight' => "nothing you don't",
            'subtitle' => 'Built specifically for service businesses like yours. Powerful enough for enterprise, simple enough to set up in minutes.',
            'items' => [
                ['icon' => '📅', 'title' => 'Smart Scheduling', 'description' => 'Booking that syncs calendars, prevents conflicts, and sends instant confirmations.', 'badge' => ''],
                ['icon' => '👥', 'title' => 'Client Management', 'description' => 'Complete profiles with attendance history, preferences, and communication logs in one view.', 'badge' => ''],
                ['icon' => '💳', 'title' => 'Seamless Payments', 'description' => 'Accept cards and process payments through Stripe, Square, Clover, and Authorize.net — with recurring billing and automatic reminders.', 'badge' => ''],
                ['icon' => '✉️', 'title' => 'Email Marketing', 'description' => 'Beautiful campaigns, automated sequences, and personalized messages that convert.', 'badge' => ''],
                ['icon' => '📊', 'title' => 'Business Analytics', 'description' => 'Real-time dashboards showing revenue trends, client retention, and growth opportunities.', 'badge' => ''],
                ['icon' => '💰', 'title' => 'Accounting Integration', 'description' => 'Sync seamlessly with QuickBooks and Xero. Say goodbye to manual data entry.', 'badge' => 'Coming Soon'],
                ['icon' => '📱', 'title' => 'Client Login Portal', 'description' => 'Clients can log in to book, pay, and manage their account online today. Beautifully designed iOS and Android apps are on the way.', 'badge' => 'App Coming Soon'],
                ['icon' => '🔔', 'title' => 'SMS Notifications', 'description' => 'Automated appointment reminders that reduce no-shows by up to 90%.', 'badge' => ''],
                ['icon' => '📋', 'title' => 'Class Management', 'description' => 'Schedule classes, manage capacity, track attendance, and handle waitlists effortlessly.', 'badge' => ''],
                ['icon' => '🎫', 'title' => 'Membership Plans', 'description' => 'Create flexible tiers, packages, and subscription plans that grow with your clients.', 'badge' => ''],
                ['icon' => '🛡️', 'title' => 'Enterprise Security', 'description' => 'Bank-level encryption, HIPAA compliance, and PCI-certified payment processing.', 'badge' => ''],
                ['icon' => '🎯', 'title' => 'Lead Management', 'description' => 'Track prospects, automate follow-ups, and convert more inquiries into paying clients.', 'badge' => ''],
            ],
        ],
        'industries' => [
            'badge' => 'Built For You',
            'title' => 'Designed for',
            'titleHighlight' => 'your industry',
            'subtitle' => 'Whether you run a dance studio, yoga space, gym, or martial arts school, DOable adapts to your unique needs.',
            'items' => [
                ['emoji' => '💃', 'title' => 'Dance Studios', 'description' => 'Private lessons, group classes, recitals, and student progression tracking.', 'features' => ['Recital management', 'Progress tracking', 'Costume inventory']],
                ['emoji' => '🧘', 'title' => 'Yoga Studios', 'description' => 'Class schedules, membership passes, and mindful client communication.', 'features' => ['Drop-in classes', 'Retreat booking', 'Teacher scheduling']],
                ['emoji' => '💪', 'title' => 'Personal Trainers', 'description' => 'Session booking, progress photos, workout plans, and automated billing.', 'features' => ['Goal tracking', 'Nutrition logs', 'Package deals']],
                ['emoji' => '🥋', 'title' => 'Martial Arts', 'description' => 'Rank progression, class scheduling, and family account handling.', 'features' => ['Rank tracking', 'Sparring logs', 'Attendance tracking']],
            ],
        ],
        'testimonials' => [
            'badge' => 'Success Stories',
            'title' => 'Loved by business',
            'titleHighlight' => 'owners',
            'subtitle' => "Join hundreds of service business owners who've transformed their operations and reclaimed their time.",
            'items' => [
                ['quote' => 'DOable transformed how I run my studio. I went from spending evenings on admin to actually having time for my family. Revenue is up 40% and I have never been happier.', 'author' => 'Sarah Martinez', 'role' => 'Owner, Rhythmic Dance Studio', 'avatar' => 'SM', 'metricValue' => '40%', 'metricLabel' => 'Revenue Growth'],
                ['quote' => 'The setup was incredibly easy. Within 20 minutes I was scheduling clients. The payment automation alone saves me 10+ hours every week.', 'author' => 'Mike Chen', 'role' => 'Elite Martial Arts Academy', 'avatar' => 'MC', 'metricValue' => '10hrs', 'metricLabel' => 'Saved Weekly'],
                ['quote' => 'I have tried 5 different platforms. DOable is the first one that actually understands what yoga studios need. The membership management is perfect.', 'author' => 'Jessica Thompson', 'role' => 'Founder, Zen Yoga Studio', 'avatar' => 'JT', 'metricValue' => '5x', 'metricLabel' => 'Better Than Others'],
                ['quote' => 'The analytics showed me where I was losing money. I restructured my pricing and now I am making $2,000 more per month with the same number of clients.', 'author' => 'David Rodriguez', 'role' => 'Personal Trainer & Gym Owner', 'avatar' => 'DR', 'metricValue' => '$2K+', 'metricLabel' => 'Monthly Extra'],
            ],
        ],
        'pricing' => [
            'badge' => 'Simple Pricing',
            'title' => 'Invest in your',
            'titleHighlight' => 'growth',
            'subtitle' => 'One simple plan with everything included. Start with a 30-day free trial.',
            'trialBanner' => '30 days free • Cancel anytime',
            'guaranteeText' => '30-day free trial • Cancel anytime',
            'plans' => [
                [
                    'name' => 'Standard',
                    'priceMonthly' => '299',
                    'priceAnnual' => '249',
                    'description' => 'Everything you need to run and grow your business.',
                    'popular' => true,
                    'features' => [
                        'Smart scheduling & calendar',
                        'Client & class management',
                        'Payment processing & recurring billing',
                        'Email & SMS marketing',
                        'Automated reminders & automations',
                        'Advanced analytics & reports',
                        'QuickBooks & Xero integration',
                        'Lead management CRM',
                        'Mobile app access',
                        'Unlimited clients',
                        'Priority support',
                    ],
                ],
            ],
        ],
        'cta' => [
            'title' => 'Ready to transform',
            'titleHighlight' => 'your business?',
            'subtitle' => "Join hundreds of small business owners who've reclaimed their time and grown their revenue with DOable.",
            'buttonText' => 'Start Your 30-Day Free Trial',
            'tagline' => 'Because your business should work for you, not the other way around.',
            'benefits' => [
                ['text' => 'Set up in under 5 minutes'],
                ['text' => 'Import existing client data'],
                ['text' => 'Cancel anytime, no contracts'],
            ],
        ],
        'contact' => [
            'badge' => 'Get in Touch',
            'title' => "Let's grow your business together",
            'subtitle' => "Have questions? Want a personalized demo? We're here to help you get started.",
            'email' => 'demo@doable.net',
            'location' => 'United States',
            'businessTypes' => ['Dance School', 'Martial Arts School', 'Gymnasium', 'Music Academy', 'Yoga Studio', 'Personal Training', 'Other'],
        ],
    ];
}

/** Recursively merge stored overrides over defaults. */
function deep_merge(array $base, array $over): array {
    foreach ($over as $k => $v) {
        if (is_array($v) && isset($base[$k]) && is_array($base[$k])
            && array_keys($v) !== range(0, count($v) - 1)) {
            // associative array -> merge recursively
            $base[$k] = deep_merge($base[$k], $v);
        } else {
            // scalar or list -> overwrite wholesale
            $base[$k] = $v;
        }
    }
    return $base;
}

/** Load merged site content (defaults + DB overrides). */
function get_content(): array {
    $defaults = default_content();
    try {
        $stmt = db()->prepare('SELECT value FROM site_content WHERE key_name = ? LIMIT 1');
        $stmt->execute(['main']);
        $row = $stmt->fetch();
        if ($row && !empty($row['value'])) {
            $stored = json_decode($row['value'], true);
            if (is_array($stored)) {
                return deep_merge($defaults, $stored);
            }
        }
    } catch (Throwable $e) {
        // table may not exist yet — fall back to defaults
    }
    return $defaults;
}

/** Persist the full content array as the 'main' override row. */
function save_content(array $content): void {
    $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = db()->prepare('SELECT id FROM site_content WHERE key_name = ? LIMIT 1');
    $stmt->execute(['main']);
    $row = $stmt->fetch();
    if ($row) {
        $u = db()->prepare('UPDATE site_content SET value = ? WHERE id = ?');
        $u->execute([$json, $row['id']]);
    } else {
        $i = db()->prepare('INSERT INTO site_content (id, key_name, value) VALUES (?, ?, ?)');
        $i->execute([uuidv4(), 'main', $json]);
    }
}
