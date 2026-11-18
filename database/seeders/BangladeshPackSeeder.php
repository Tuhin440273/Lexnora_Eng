<?php

namespace Database\Seeders;

use App\Modules\Learning\Enums\ContentType;

/**
 * Two courses about everyday life in Bangladesh (A1 and A2), with Bengali
 * translations, words and phrases for Easy and Medium mode, and vocabulary.
 * It is separate from ContentPackSeeder, so the first pack stays as it is.
 * Safe to run again: everything is matched by slug, title or text.
 *
 *     php artisan db:seed --class=BangladeshPackSeeder
 *
 * The Bengali was written for this project. Have a native speaker read it
 * (see the review sheet) before real learners use it.
 */
class BangladeshPackSeeder extends SampleContentSeeder
{
    protected function categoryData(): array
    {
        return parent::categoryData() + [
            'bangladesh' => ['Life in Bangladesh', 'Everyday English with the words, food and places of Bangladesh.', 6],
        ];
    }

    protected function courseData(): array
    {
        $word = ContentType::Word;
        $phrase = ContentType::Phrase;

        // The keys (10 and 11) keep these courses after the first pack in the course list.
        return [
            10 => [
                'slug' => 'life-in-bangladesh',
                'title' => 'Life in Bangladesh',
                'description' => 'Talk about your family, your home, your food and the festivals you love.',
                'category' => 'bangladesh',
                'cefr' => 'A1',
                'difficulty' => 1,
                'chapters' => [
                    [
                        'title' => 'Family and Home',
                        'description' => 'The people and the place you come home to.',
                        'lessons' => [
                            [
                                'title' => 'My Family',
                                'description' => 'Parents, brothers, sisters and grandparents.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'This is my family.', 'bn' => 'এটি আমার পরিবার।', 'chunks' => [
                                        [$word, 'family', 'পরিবার'],
                                        [$phrase, 'my family', 'আমার পরিবার'],
                                    ]],
                                    ['en' => 'My father works in a bank.', 'bn' => 'আমার বাবা ব্যাংকে কাজ করেন।', 'chunks' => [
                                        [$word, 'bank', 'ব্যাংক'],
                                        [$phrase, 'works in a bank', 'ব্যাংকে কাজ করেন'],
                                    ]],
                                    ['en' => 'My mother cooks very well.', 'bn' => 'আমার মা খুব ভালো রান্না করেন।'],
                                    ['en' => 'I have one brother and one sister.', 'bn' => 'আমার এক ভাই আর এক বোন আছে।'],
                                    ['en' => 'My grandmother lives in our village.', 'bn' => 'আমার দাদি আমাদের গ্রামে থাকেন।', 'chunks' => [
                                        [$word, 'village', 'গ্রাম'],
                                    ]],
                                    ['en' => 'We visit her every Eid.', 'bn' => 'আমরা প্রতি ঈদে তাঁর সঙ্গে দেখা করতে যাই।'],
                                ],
                            ],
                            [
                                'title' => 'Our Home',
                                'description' => 'Rooms, the verandah and the yard.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'Our house has three rooms.', 'bn' => 'আমাদের বাড়িতে তিনটি ঘর আছে।'],
                                    ['en' => 'The kitchen is small, but clean.', 'bn' => 'রান্নাঘরটা ছোট, কিন্তু পরিষ্কার।'],
                                    ['en' => 'We sit on the verandah in the evening.', 'bn' => 'আমরা সন্ধ্যায় বারান্দায় বসি।', 'chunks' => [
                                        [$word, 'verandah', 'বারান্দা'],
                                        [$phrase, 'in the evening', 'সন্ধ্যায়'],
                                    ]],
                                    ['en' => 'There is a mango tree in the yard.', 'bn' => 'উঠানে একটি আমগাছ আছে।'],
                                    ['en' => 'Please take off your shoes at the door.', 'bn' => 'দয়া করে দরজার কাছে জুতা খুলে রাখুন।'],
                                    ['en' => 'The power went out for an hour.', 'bn' => 'এক ঘণ্টার জন্য বিদ্যুৎ চলে গিয়েছিল।'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Food and Festivals',
                        'description' => 'What we eat, and how we celebrate.',
                        'lessons' => [
                            [
                                'title' => 'Rice, Fish and Tea',
                                'description' => 'Everyday food and drink.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'We eat rice with fish and lentils.', 'bn' => 'আমরা ভাতের সঙ্গে মাছ আর ডাল খাই।', 'chunks' => [
                                        [$word, 'rice', 'ভাত'],
                                        [$word, 'lentils', 'ডাল'],
                                        [$phrase, 'rice with fish', 'মাছ দিয়ে ভাত'],
                                    ]],
                                    ['en' => 'Hilsha is my favourite fish.', 'bn' => 'ইলিশ আমার প্রিয় মাছ।', 'chunks' => [
                                        [$word, 'Hilsha', 'ইলিশ'],
                                        [$phrase, 'my favourite fish', 'আমার প্রিয় মাছ'],
                                    ]],
                                    ['en' => 'Would you like a cup of tea?', 'bn' => 'আপনি কি এক কাপ চা খাবেন?'],
                                    ['en' => 'The tea is hot and sweet.', 'bn' => 'চা গরম আর মিষ্টি।'],
                                    ['en' => 'My mother makes pitha in winter.', 'bn' => 'আমার মা শীতকালে পিঠা বানান।'],
                                    ['en' => 'Please pass me the salt.', 'bn' => 'দয়া করে আমাকে লবণটা দিন।'],
                                ],
                            ],
                            [
                                'title' => 'Pohela Boishakh and Eid',
                                'description' => 'Two days the whole family waits for.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'Pohela Boishakh is the Bengali New Year.', 'bn' => 'পহেলা বৈশাখ বাংলা নববর্ষ।', 'note' => 'Pohela Boishakh is the first day of the Bengali year. In Bangladesh it falls on 14 April.', 'chunks' => [
                                        [$phrase, 'New Year', 'নববর্ষ'],
                                    ]],
                                    ['en' => 'On this day, people wear red and white clothes.', 'bn' => 'এই দিনে মানুষ লাল-সাদা পোশাক পরে।'],
                                    ['en' => 'We eat panta bhat and fried fish.', 'bn' => 'আমরা পান্তা ভাত আর ভাজা মাছ খাই।'],
                                    ['en' => 'On Eid, we wear new clothes.', 'bn' => 'ঈদে আমরা নতুন পোশাক পরি।', 'chunks' => [
                                        [$word, 'Eid', 'ঈদ'],
                                    ]],
                                    ['en' => 'Children get Eidi from their elders.', 'bn' => 'বড়দের কাছ থেকে শিশুরা ঈদি পায়।', 'note' => 'Eidi is the gift of money that children receive on Eid.'],
                                    ['en' => 'Eid Mubarak to you and your family!', 'bn' => 'আপনাকে ও আপনার পরিবারকে ঈদ মোবারক!', 'note' => 'People say this to wish each other a happy Eid.'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            11 => [
                'slug' => 'getting-around-bangladesh',
                'title' => 'Getting Around Bangladesh',
                'description' => 'Ask the way, take a rickshaw or a bus, and talk about the rain and the seasons.',
                'category' => 'travel',
                'cefr' => 'A2',
                'difficulty' => 2,
                'chapters' => [
                    [
                        'title' => 'In the City',
                        'description' => 'Rickshaws, buses and asking for directions.',
                        'lessons' => [
                            [
                                'title' => 'Rickshaw and Bus',
                                'description' => 'Fares, stops and getting off.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'How much is the fare to the station?', 'bn' => 'স্টেশন পর্যন্ত ভাড়া কত?', 'chunks' => [
                                        [$word, 'fare', 'ভাড়া'],
                                        [$word, 'station', 'স্টেশন'],
                                        [$phrase, 'to the station', 'স্টেশন পর্যন্ত'],
                                    ]],
                                    ['en' => 'Please go straight and turn left.', 'bn' => 'দয়া করে সোজা গিয়ে বাঁয়ে ঘুরুন।'],
                                    ['en' => 'The rickshaw puller knows a short way.', 'bn' => 'রিকশাওয়ালা একটা কাছের রাস্তা চেনেন।', 'chunks' => [
                                        [$word, 'rickshaw', 'রিকশা'],
                                    ]],
                                    ['en' => 'The bus is full, so I will wait for the next one.', 'bn' => 'বাসটা ভর্তি, তাই আমি পরেরটার জন্য অপেক্ষা করব।'],
                                    ['en' => 'Does this bus go to Mirpur?', 'bn' => 'এই বাস কি মিরপুর যায়?'],
                                    ['en' => 'Please stop here, I will get off.', 'bn' => 'দয়া করে এখানে থামুন, আমি নামব।'],
                                ],
                            ],
                            [
                                'title' => 'Asking the Way',
                                'description' => 'Polite questions and clear answers.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'Excuse me, where is the nearest bus stand?', 'bn' => 'মাফ করবেন, সবচেয়ে কাছের বাস স্ট্যান্ড কোথায়?'],
                                    ['en' => 'It is about ten minutes on foot.', 'bn' => 'হেঁটে প্রায় দশ মিনিট লাগবে।'],
                                    ['en' => 'Is there a pharmacy near here?', 'bn' => 'এখানকার কাছে কি কোনো ওষুধের দোকান আছে?'],
                                    ['en' => 'Cross the road and you will see a mosque.', 'bn' => 'রাস্তা পার হলেই একটা মসজিদ দেখবেন।', 'chunks' => [
                                        [$word, 'mosque', 'মসজিদ'],
                                    ]],
                                    ['en' => 'I am lost. Can you help me, please?', 'bn' => 'আমি পথ হারিয়ে ফেলেছি। আপনি কি আমাকে সাহায্য করতে পারবেন?'],
                                    ['en' => 'Thank you very much for your help.', 'bn' => 'আপনার সাহায্যের জন্য অনেক ধন্যবাদ।'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Weather and Seasons',
                        'description' => 'Rain, rivers and the six seasons.',
                        'lessons' => [
                            [
                                'title' => 'Rain and Monsoon',
                                'description' => 'The rainy season and how it changes the day.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'It rains a lot in July.', 'bn' => 'জুলাই মাসে অনেক বৃষ্টি হয়।', 'chunks' => [
                                        [$phrase, 'a lot', 'অনেক'],
                                    ]],
                                    ['en' => 'The road is full of water.', 'bn' => 'রাস্তা পানিতে ভরে গেছে।'],
                                    ['en' => 'Take an umbrella, because the sky is dark.', 'bn' => 'ছাতা নিয়ে যান, কারণ আকাশ অন্ধকার।', 'chunks' => [
                                        [$word, 'umbrella', 'ছাতা'],
                                    ]],
                                    ['en' => 'The farmers are happy about the rain.', 'bn' => 'বৃষ্টির জন্য কৃষকেরা খুশি।'],
                                    ['en' => 'The river rises in the rainy season.', 'bn' => 'বর্ষাকালে নদীর পানি বাড়ে।'],
                                    ['en' => 'We cannot play outside today.', 'bn' => 'আজ আমরা বাইরে খেলতে পারব না।'],
                                ],
                            ],
                            [
                                'title' => 'Six Seasons',
                                'description' => 'Summer, rain, autumn, late autumn, winter and spring.',
                                'minutes' => 7,
                                'sentences' => [
                                    ['en' => 'Bangladesh has six seasons.', 'bn' => 'বাংলাদেশে ছয়টি ঋতু আছে।', 'chunks' => [
                                        [$word, 'six', 'ছয়'],
                                        [$word, 'seasons', 'ঋতু'],
                                    ]],
                                    ['en' => 'Summer is hot and the air is wet.', 'bn' => 'গ্রীষ্মকাল গরম আর বাতাস আর্দ্র।'],
                                    ['en' => 'Winter is cool and dry.', 'bn' => 'শীতকাল ঠান্ডা আর শুকনো।'],
                                    ['en' => 'In spring, the trees have new leaves.', 'bn' => 'বসন্তে গাছে নতুন পাতা আসে।'],
                                    ['en' => 'The autumn sky is clear and blue.', 'bn' => 'শরতের আকাশ পরিষ্কার আর নীল।'],
                                    ['en' => 'My favourite season is the rainy season.', 'bn' => 'আমার প্রিয় ঋতু বর্ষাকাল।'],
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
            ['family', 'word', 'noun', 'a group of people who are related to each other', 'পরিবার', '/ˈfæməli/', 'This is my family.', 'A1'],
            ['village', 'word', 'noun', 'a very small town in the countryside', 'গ্রাম', '/ˈvɪlɪdʒ/', 'My grandmother lives in our village.', 'A1'],
            ['verandah', 'word', 'noun', 'a covered area along the side of a house', 'বারান্দা', '/vəˈrændə/', 'We sit on the verandah in the evening.', 'A2'],
            ['rice', 'word', 'noun', 'the small grains that are cooked and eaten as the main food', 'ভাত, চাল', '/raɪs/', 'We eat rice with fish and lentils.', 'A1'],
            ['lentils', 'word', 'noun', 'small round seeds that are cooked as dal', 'ডাল', '/ˈlentɪlz/', 'We eat rice with fish and lentils.', 'A1'],
            ['hilsha', 'word', 'noun', 'a popular silver fish of Bangladesh', 'ইলিশ মাছ', null, 'Hilsha is my favourite fish.', 'A1'],
            ['tea', 'word', 'noun', 'a hot drink made with tea leaves', 'চা', '/tiː/', 'Would you like a cup of tea?', 'A1'],
            ['Eid', 'word', 'noun', 'a Muslim festival of joy and giving', 'ঈদ', '/iːd/', 'On Eid, we wear new clothes.', 'A1'],
            ['New Year', 'phrase', 'noun', 'the first day of a new year', 'নববর্ষ', null, 'Pohela Boishakh is the Bengali New Year.', 'A1'],
            ['fare', 'word', 'noun', 'the money you pay for a journey', 'ভাড়া', '/feə/', 'How much is the fare to the station?', 'A2'],
            ['station', 'word', 'noun', 'a place where buses or trains stop', 'স্টেশন', '/ˈsteɪʃn/', 'How much is the fare to the station?', 'A1'],
            ['rickshaw', 'word', 'noun', 'a small vehicle with three wheels, pulled by a person on a bicycle', 'রিকশা', '/ˈrɪkʃɔː/', 'The rickshaw puller knows a short way.', 'A1'],
            ['mosque', 'word', 'noun', 'a building where Muslims pray', 'মসজিদ', '/mɒsk/', 'Cross the road and you will see a mosque.', 'A2'],
            ['umbrella', 'word', 'noun', 'something you hold over your head to keep the rain off', 'ছাতা', '/ʌmˈbrelə/', 'Take an umbrella, because the sky is dark.', 'A1'],
            ['farmer', 'word', 'noun', 'a person who grows crops or keeps animals', 'কৃষক', '/ˈfɑːmə/', 'The farmers are happy about the rain.', 'A2'],
            ['river', 'word', 'noun', 'a large stream of water that flows to the sea', 'নদী', '/ˈrɪvə/', 'The river rises in the rainy season.', 'A1'],
            ['season', 'word', 'noun', 'one of the parts of the year, such as summer or winter', 'ঋতু', '/ˈsiːzn/', 'Bangladesh has six seasons.', 'A1'],
            ['spring', 'word', 'noun', 'the season after winter, when plants start to grow', 'বসন্তকাল', '/sprɪŋ/', 'In spring, the trees have new leaves.', 'A2'],
        ];
    }
}
