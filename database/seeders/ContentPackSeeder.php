<?php

namespace Database\Seeders;

use App\Modules\Learning\Enums\ContentType;

/**
 * Five ready-to-use courses (A1 to B2) with Bengali translations, words and
 * phrases for Easy and Medium mode, and a vocabulary list. Safe to run again:
 * everything is matched by slug, title or text.
 *
 *     php artisan db:seed --class=ContentPackSeeder
 *
 * There is no audio yet. Upload recordings from the lesson page; until then
 * the browser's voice reads the text aloud.
 */
class ContentPackSeeder extends SampleContentSeeder
{
    protected function categoryData(): array
    {
        return parent::categoryData() + [
            'shopping' => ['Shopping & Money', 'Markets, prices, paying and returning things.', 4],
            'health' => ['Health', 'Feeling unwell, doctors and pharmacies.', 5],
        ];
    }

    protected function courseData(): array
    {
        $word = ContentType::Word;
        $phrase = ContentType::Phrase;

        return [
            [
                'slug' => 'my-daily-routine',
                'title' => 'My Daily Routine',
                'description' => 'Talk about your day, from waking up to going to bed, and about your free time.',
                'category' => 'daily-life',
                'cefr' => 'A1',
                'difficulty' => 1,
                'chapters' => [
                    [
                        'title' => 'Morning and Evening',
                        'description' => 'What you do at the start and the end of the day.',
                        'lessons' => [
                            [
                                'title' => 'Waking Up',
                                'description' => 'Time, breakfast and getting to work.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'I wake up at seven o\'clock.', 'bn' => 'আমি সাতটায় ঘুম থেকে উঠি।', 'chunks' => [
                                        [$word, 'seven', 'সাত'],
                                        [$phrase, 'I wake up', 'আমি ঘুম থেকে উঠি'],
                                        [$phrase, 'at seven o\'clock', 'সাতটায়'],
                                    ]],
                                    ['en' => 'I brush my teeth and take a shower.', 'bn' => 'আমি দাঁত ব্রাশ করি এবং গোসল করি।'],
                                    ['en' => 'What time do you get up?', 'bn' => 'আপনি কয়টায় ঘুম থেকে ওঠেন?'],
                                    ['en' => 'I usually have breakfast at home.', 'bn' => 'আমি সাধারণত বাসায় নাস্তা করি।'],
                                    ['en' => 'My bus leaves at eight.', 'bn' => 'আমার বাস আটটায় ছাড়ে।'],
                                    ['en' => 'I am never late for work.', 'bn' => 'আমি কখনো কাজে দেরি করি না।'],
                                ],
                            ],
                            [
                                'title' => 'Evening at Home',
                                'description' => 'Dinner, television and bedtime.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'I come home at six in the evening.', 'bn' => 'আমি সন্ধ্যা ছয়টায় বাসায় ফিরি।'],
                                    ['en' => 'We eat dinner together.', 'bn' => 'আমরা একসাথে রাতের খাবার খাই।'],
                                    ['en' => 'After dinner, I watch television.', 'bn' => 'রাতের খাবারের পর আমি টেলিভিশন দেখি।'],
                                    ['en' => 'Do you help with the housework?', 'bn' => 'আপনি কি ঘরের কাজে সাহায্য করেন?'],
                                    ['en' => 'I go to bed at eleven.', 'bn' => 'আমি এগারোটায় ঘুমাতে যাই।'],
                                    ['en' => 'Good night, sleep well.', 'bn' => 'শুভরাত্রি, ভালো করে ঘুমান।'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'The Weekend',
                        'description' => 'Family, hobbies and free time.',
                        'lessons' => [
                            [
                                'title' => 'Free Time',
                                'description' => 'What people like to do when they are not working.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'On Fridays, I visit my grandparents.', 'bn' => 'শুক্রবার আমি আমার দাদা-দাদির সাথে দেখা করতে যাই।'],
                                    ['en' => 'She likes reading books in the park.', 'bn' => 'সে পার্কে বই পড়তে পছন্দ করে।'],
                                    ['en' => 'We play cricket on Saturday afternoon.', 'bn' => 'আমরা শনিবার বিকেলে ক্রিকেট খেলি।'],
                                    ['en' => 'Would you like to go for a walk?', 'bn' => 'আপনি কি হাঁটতে যেতে চান?', 'note' => '"Would you like to..." is a polite way to invite someone.'],
                                    ['en' => 'I am cooking rice and fish today.', 'bn' => 'আমি আজ ভাত আর মাছ রান্না করছি।'],
                                    ['en' => 'It is a beautiful day.', 'bn' => 'আজ একটা সুন্দর দিন।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'shopping-and-money',
                'title' => 'Shopping and Money',
                'description' => 'Ask about prices, bargain politely, pay and return things.',
                'category' => 'shopping',
                'cefr' => 'A2',
                'difficulty' => 2,
                'chapters' => [
                    [
                        'title' => 'At the Market',
                        'description' => 'Prices, sizes and quantities.',
                        'lessons' => [
                            [
                                'title' => 'Asking Prices',
                                'description' => 'How much is it, and can it be cheaper?',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'How much does this shirt cost?', 'bn' => 'এই শার্টটার দাম কত?', 'chunks' => [
                                        [$word, 'shirt', 'শার্ট'],
                                        [$phrase, 'How much', 'কত'],
                                        [$phrase, 'does this shirt cost', 'এই শার্টের দাম'],
                                    ]],
                                    ['en' => 'It costs five hundred taka.', 'bn' => 'এটার দাম পাঁচশো টাকা।'],
                                    ['en' => 'That is too expensive for me.', 'bn' => 'এটা আমার জন্য খুব বেশি দামি।'],
                                    ['en' => 'Can you give me a discount?', 'bn' => 'আপনি কি আমাকে কিছু ছাড় দিতে পারেন?'],
                                    ['en' => 'I would like two kilos of rice.', 'bn' => 'আমি দুই কেজি চাল চাই।', 'chunks' => [
                                        [$word, 'rice', 'চাল'],
                                        [$phrase, 'I would like', 'আমি চাই'],
                                        [$phrase, 'two kilos of rice', 'দুই কেজি চাল'],
                                    ]],
                                    ['en' => 'Do you have a smaller size?', 'bn' => 'আপনার কাছে কি ছোট সাইজ আছে?'],
                                ],
                            ],
                            [
                                'title' => 'Paying',
                                'description' => 'Cash, mobile banking, receipts and returns.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'Where can I pay?', 'bn' => 'আমি কোথায় টাকা দিতে পারি?'],
                                    ['en' => 'I will pay in cash.', 'bn' => 'আমি নগদে দেব।'],
                                    ['en' => 'Can I pay by mobile banking?', 'bn' => 'আমি কি মোবাইল ব্যাংকিংয়ে টাকা দিতে পারি?'],
                                    ['en' => 'Here is your receipt.', 'bn' => 'এই নিন আপনার রসিদ।'],
                                    ['en' => 'I think you gave me the wrong change.', 'bn' => 'আমার মনে হয় আপনি আমাকে ভুল খুচরা টাকা ফেরত দিয়েছেন।'],
                                    ['en' => 'I would like to return this, please.', 'bn' => 'আমি এটা ফেরত দিতে চাই, দয়া করে।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'health-and-the-doctor',
                'title' => 'Health and the Doctor',
                'description' => 'Describe how you feel, understand a doctor and buy medicine.',
                'category' => 'health',
                'cefr' => 'A2',
                'difficulty' => 2,
                'chapters' => [
                    [
                        'title' => 'Feeling Unwell',
                        'description' => 'Talking to a doctor or a pharmacist.',
                        'lessons' => [
                            [
                                'title' => 'Describing Symptoms',
                                'description' => 'Pain, fever and how long it has lasted.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'I have a headache and a sore throat.', 'bn' => 'আমার মাথাব্যথা আর গলাব্যথা আছে।', 'chunks' => [
                                        [$word, 'headache', 'মাথাব্যথা'],
                                        [$word, 'throat', 'গলা'],
                                        [$phrase, 'a sore throat', 'গলাব্যথা'],
                                    ]],
                                    ['en' => 'I have had a fever since yesterday.', 'bn' => 'আমার গতকাল থেকে জ্বর।', 'note' => '"Since" is used with a point in time: since yesterday, since Monday.'],
                                    ['en' => 'My stomach hurts when I eat.', 'bn' => 'খেলে আমার পেট ব্যথা করে।'],
                                    ['en' => 'I feel dizzy and very tired.', 'bn' => 'আমার মাথা ঘুরছে আর খুব ক্লান্ত লাগছে।'],
                                    ['en' => 'How long have you felt like this?', 'bn' => 'আপনি কতদিন ধরে এমন বোধ করছেন?'],
                                    ['en' => 'Do you have any allergies?', 'bn' => 'আপনার কি কোনো অ্যালার্জি আছে?'],
                                ],
                            ],
                            [
                                'title' => 'At the Pharmacy',
                                'description' => 'Medicine, instructions and advice.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'I need something for a cough.', 'bn' => 'আমার কাশির জন্য কিছু দরকার।'],
                                    ['en' => 'Take one tablet after every meal.', 'bn' => 'প্রতিবার খাবারের পর একটি করে ট্যাবলেট খান।'],
                                    ['en' => 'Drink plenty of water and rest.', 'bn' => 'প্রচুর পানি পান করুন আর বিশ্রাম নিন।'],
                                    ['en' => 'Do not take this medicine on an empty stomach.', 'bn' => 'খালি পেটে এই ওষুধ খাবেন না।'],
                                    ['en' => 'You should see a doctor if it gets worse.', 'bn' => 'অবস্থা খারাপ হলে আপনার ডাক্তার দেখানো উচিত।'],
                                    ['en' => 'I would like to make an appointment.', 'bn' => 'আমি একটা অ্যাপয়েন্টমেন্ট নিতে চাই।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'work-and-office-english',
                'title' => 'Work and Office English',
                'description' => 'Meetings, emails and phone calls in a professional setting.',
                'category' => 'work-business',
                'cefr' => 'B1',
                'difficulty' => 3,
                'chapters' => [
                    [
                        'title' => 'In the Office',
                        'description' => 'Everyday professional communication.',
                        'lessons' => [
                            [
                                'title' => 'Meetings',
                                'description' => 'Starting, agreeing, disagreeing and postponing.',
                                'minutes' => 9,
                                'sentences' => [
                                    ['en' => 'Let\'s start the meeting with a quick update.', 'bn' => 'চলুন একটা সংক্ষিপ্ত হালনাগাদ দিয়ে সভা শুরু করি।', 'chunks' => [
                                        [$word, 'meeting', 'সভা'],
                                        [$phrase, 'Let\'s start', 'চলুন শুরু করি'],
                                        [$phrase, 'a quick update', 'একটা সংক্ষিপ্ত হালনাগাদ'],
                                    ]],
                                    ['en' => 'Could you send me the report by Friday?', 'bn' => 'আপনি কি শুক্রবারের মধ্যে আমাকে প্রতিবেদনটি পাঠাতে পারবেন?', 'chunks' => [
                                        [$word, 'report', 'প্রতিবেদন'],
                                        [$phrase, 'by Friday', 'শুক্রবারের মধ্যে'],
                                    ]],
                                    ['en' => 'I am afraid I cannot attend tomorrow\'s meeting.', 'bn' => 'দুঃখিত, আমি আগামীকালের সভায় উপস্থিত থাকতে পারব না।', 'note' => '"I am afraid" is a polite way to give bad news.'],
                                    ['en' => 'We need to finish this project before the deadline.', 'bn' => 'সময়সীমার আগে আমাদের এই প্রকল্পটি শেষ করতে হবে।'],
                                    ['en' => 'That is a good point, but I have a different opinion.', 'bn' => 'এটা ভালো একটা কথা, কিন্তু আমার মত আলাদা।'],
                                    ['en' => 'Let me check my calendar and get back to you.', 'bn' => 'আমি আমার ক্যালেন্ডার দেখে আপনাকে জানাচ্ছি।'],
                                ],
                            ],
                            [
                                'title' => 'Emails and Calls',
                                'description' => 'Polite phrases for writing and speaking.',
                                'minutes' => 9,
                                'sentences' => [
                                    ['en' => 'I am writing to ask about your delivery times.', 'bn' => 'আপনাদের সরবরাহের সময় সম্পর্কে জানতে আমি লিখছি।'],
                                    ['en' => 'Please find the attached document.', 'bn' => 'সংযুক্ত নথিটি দেখুন।'],
                                    ['en' => 'Thank you for your quick reply.', 'bn' => 'দ্রুত উত্তর দেওয়ার জন্য ধন্যবাদ।'],
                                    ['en' => 'Could you speak more slowly, please?', 'bn' => 'আপনি কি আরেকটু ধীরে বলতে পারবেন, দয়া করে?'],
                                    ['en' => 'I will call you back in ten minutes.', 'bn' => 'আমি দশ মিনিট পর আপনাকে ফোন করব।'],
                                    ['en' => 'I look forward to hearing from you.', 'bn' => 'আপনার উত্তরের অপেক্ষায় রইলাম।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'opinions-and-current-events',
                'title' => 'Opinions and Current Events',
                'description' => 'Longer, more complex sentences for talking about the news and giving reasons.',
                'category' => 'daily-life',
                'cefr' => 'B2',
                'difficulty' => 4,
                'chapters' => [
                    [
                        'title' => 'Talking About the News',
                        'description' => 'Opinions, research and public life.',
                        'lessons' => [
                            [
                                'title' => 'Giving Your Opinion',
                                'description' => 'Express a view and support it.',
                                'minutes' => 10,
                                'sentences' => [
                                    ['en' => 'In my opinion, public transport should be cheaper.', 'bn' => 'আমার মতে, গণপরিবহন আরও সস্তা হওয়া উচিত।'],
                                    ['en' => 'I strongly believe that education is the key to a better future.', 'bn' => 'আমি দৃঢ়ভাবে বিশ্বাস করি শিক্ষাই উন্নত ভবিষ্যতের চাবিকাঠি।'],
                                    ['en' => 'Although prices have risen, many families still manage to save.', 'bn' => 'দাম বেড়েছে, তবু অনেক পরিবার এখনও সঞ্চয় করতে পারছে।'],
                                    ['en' => 'The government announced new measures to reduce air pollution.', 'bn' => 'সরকার বায়ুদূষণ কমানোর জন্য নতুন পদক্ষেপের ঘোষণা দিয়েছে।'],
                                    ['en' => 'Researchers have found a link between sleep and memory.', 'bn' => 'গবেষকরা ঘুম ও স্মৃতির মধ্যে একটি সম্পর্ক খুঁজে পেয়েছেন।'],
                                    ['en' => 'It is difficult to predict how the economy will change next year.', 'bn' => 'আগামী বছর অর্থনীতি কীভাবে বদলাবে তা অনুমান করা কঠিন।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function vocabularyData(): array
    {
        return [
            ['wake up', 'phrase', 'verb', 'to stop sleeping', 'ঘুম থেকে ওঠা', null, 'I wake up at seven o\'clock.', 'A1'],
            ['breakfast', 'word', 'noun', 'the first meal of the day', 'সকালের নাস্তা', '/ˈbrekfəst/', 'I usually have breakfast at home.', 'A1'],
            ['shower', 'word', 'noun', 'washing your body under running water', 'গোসল (ঝরনার নিচে)', '/ˈʃaʊə/', 'I brush my teeth and take a shower.', 'A1'],
            ['housework', 'word', 'noun', 'work done in a home, such as cleaning and cooking', 'ঘরের কাজ', '/ˈhaʊswɜːk/', 'Do you help with the housework?', 'A2'],
            ['cost', 'word', 'verb', 'to have a price of', 'দাম হওয়া', '/kɒst/', 'How much does this shirt cost?', 'A1'],
            ['expensive', 'word', 'adjective', 'costing a lot of money', 'দামি', '/ɪkˈspensɪv/', 'That is too expensive for me.', 'A1'],
            ['discount', 'word', 'noun', 'a reduction in price', 'ছাড়', '/ˈdɪskaʊnt/', 'Can you give me a discount?', 'A2'],
            ['cash', 'word', 'noun', 'money in coins and notes', 'নগদ টাকা', '/kæʃ/', 'I will pay in cash.', 'A2'],
            ['receipt', 'word', 'noun', 'a paper that shows you have paid', 'রসিদ', '/rɪˈsiːt/', 'Here is your receipt.', 'A2'],
            ['headache', 'word', 'noun', 'a pain in your head', 'মাথাব্যথা', '/ˈhedeɪk/', 'I have a headache and a sore throat.', 'A1'],
            ['sore throat', 'phrase', 'noun', 'a painful throat', 'গলাব্যথা', null, 'I have a headache and a sore throat.', 'A2'],
            ['fever', 'word', 'noun', 'a high body temperature when you are ill', 'জ্বর', '/ˈfiːvə/', 'I have had a fever since yesterday.', 'A2'],
            ['dizzy', 'word', 'adjective', 'feeling that everything is spinning', 'মাথা ঘোরা', '/ˈdɪzi/', 'I feel dizzy and very tired.', 'B1'],
            ['allergy', 'word', 'noun', 'a reaction of the body to something such as food or dust', 'অ্যালার্জি', '/ˈælədʒi/', 'Do you have any allergies?', 'B1'],
            ['medicine', 'word', 'noun', 'something you take to treat an illness', 'ওষুধ', '/ˈmedsn/', 'Do not take this medicine on an empty stomach.', 'A2'],
            ['appointment', 'word', 'noun', 'an arranged time to see someone', 'অ্যাপয়েন্টমেন্ট (সাক্ষাতের নির্ধারিত সময়)', '/əˈpɔɪntmənt/', 'I would like to make an appointment.', 'A2'],
            ['deadline', 'word', 'noun', 'the last time by which something must be done', 'সময়সীমা', '/ˈdedlaɪn/', 'We need to finish this project before the deadline.', 'B1'],
            ['attend', 'word', 'verb', 'to be present at an event', 'উপস্থিত থাকা', '/əˈtend/', 'I am afraid I cannot attend tomorrow\'s meeting.', 'B1'],
            ['reply', 'word', 'noun', 'an answer', 'উত্তর', '/rɪˈplaɪ/', 'Thank you for your quick reply.', 'A2'],
            ['calendar', 'word', 'noun', 'a chart that shows the days and months of the year', 'ক্যালেন্ডার', '/ˈkælɪndə/', 'Let me check my calendar and get back to you.', 'A2'],
            ['opinion', 'word', 'noun', 'what you think about something', 'মতামত', '/əˈpɪnjən/', 'That is a good point, but I have a different opinion.', 'B1'],
            ['government', 'word', 'noun', 'the people who run a country', 'সরকার', '/ˈɡʌvənmənt/', 'The government announced new measures to reduce air pollution.', 'B1'],
            ['pollution', 'word', 'noun', 'harmful substances in the air, water or soil', 'দূষণ', '/pəˈluːʃn/', 'The government announced new measures to reduce air pollution.', 'B2'],
            ['researcher', 'word', 'noun', 'a person who studies something carefully to find new facts', 'গবেষক', '/rɪˈsɜːtʃə/', 'Researchers have found a link between sleep and memory.', 'B2'],
            ['memory', 'word', 'noun', 'the ability to remember', 'স্মৃতি', '/ˈmeməri/', 'Researchers have found a link between sleep and memory.', 'B1'],
            ['predict', 'word', 'verb', 'to say what will happen in the future', 'অনুমান করা', '/prɪˈdɪkt/', 'It is difficult to predict how the economy will change next year.', 'B2'],
            ['economy', 'word', 'noun', 'the system of money, trade and jobs in a country', 'অর্থনীতি', '/ɪˈkɒnəmi/', 'It is difficult to predict how the economy will change next year.', 'B2'],
        ];
    }
}
