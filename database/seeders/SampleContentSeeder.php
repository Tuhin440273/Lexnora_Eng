<?php

namespace Database\Seeders;

use App\Modules\Admin\Services\VocabularyLinker;
use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Enums\ContentType;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Realistic demo content. No audio is attached: audio files are uploaded by
 * admins (Phase 3). Safe to run many times (everything is matched by slug/title).
 */
class SampleContentSeeder extends Seeder
{
    public function __construct(protected VocabularyLinker $linker) {}

    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = $this->seedCategories();
            $this->seedCourses($categories);
            $this->seedVocabulary();
            $this->relinkAll();
        });
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $categories = [];

        foreach ($this->categoryData() as $slug => [$name, $description, $order]) {
            $categories[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'sort_order' => $order, 'is_active' => true]
            );
        }

        return $categories;
    }

    /** @return array<string, array{0: string, 1: string, 2: int}> */
    protected function categoryData(): array
    {
        return [
            'daily-life' => ['Daily Life', 'Everyday situations: greetings, shopping, eating out.', 1],
            'travel' => ['Travel', 'Airports, hotels, directions and getting around.', 2],
            'work-business' => ['Work & Business', 'Meetings, emails and professional conversations.', 3],
        ];
    }

    /** @param array<string, Category> $categories */
    private function seedCourses(array $categories): void
    {
        foreach ($this->courseData() as $order => $data) {
            $course = Course::updateOrCreate(['slug' => $data['slug']], [
                'category_id' => $categories[$data['category']]->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'cefr_level' => $data['cefr'],
                'difficulty' => $data['difficulty'],
                'is_published' => true,
                'published_at' => now(),
                'sort_order' => $order,
            ]);

            foreach ($data['chapters'] as $chapterOrder => $chapterData) {
                $chapter = Chapter::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $chapterData['title']],
                    ['description' => $chapterData['description'], 'sort_order' => $chapterOrder, 'is_published' => true]
                );

                foreach ($chapterData['lessons'] as $lessonOrder => $lessonData) {
                    $lesson = Lesson::updateOrCreate(
                        ['chapter_id' => $chapter->id, 'title' => $lessonData['title']],
                        [
                            'description' => $lessonData['description'],
                            'difficulty' => $data['difficulty'],
                            'estimated_minutes' => $lessonData['minutes'],
                            'sort_order' => $lessonOrder,
                            'is_published' => true,
                        ]
                    );

                    $this->seedSentences($lesson, $lessonData['sentences']);
                }
            }
        }
    }

    /** @param list<array<string, mixed>> $sentences */
    private function seedSentences(Lesson $lesson, array $sentences): void
    {
        foreach ($sentences as $index => $row) {
            $sentence = Sentence::updateOrCreate(
                ['lesson_id' => $lesson->id, 'parent_id' => null, 'english_text' => $row['en']],
                [
                    'content_type' => ContentType::Sentence,
                    'bengali_text' => $row['bn'],
                    'explanation' => $row['note'] ?? null,
                    'sort_order' => $index + 1,
                ]
            );

            // Word and phrase chunks (with their own audio later) for EASY / MEDIUM mode.
            foreach ($row['chunks'] ?? [] as $chunkIndex => [$type, $en, $bn]) {
                Sentence::updateOrCreate(
                    ['lesson_id' => $lesson->id, 'parent_id' => $sentence->id, 'english_text' => $en],
                    ['content_type' => $type, 'bengali_text' => $bn, 'sort_order' => $chunkIndex + 1]
                );
            }
        }
    }

    /** New lessons may use words that were added earlier, so every active entry is matched again. */
    private function relinkAll(): void
    {
        Vocabulary::where('is_active', true)->each(fn (Vocabulary $entry) => $this->linker->linkVocabulary($entry));
    }

    private function seedVocabulary(): void
    {
        foreach ($this->vocabularyData() as [$term, $type, $pos, $meaningEn, $meaningBn, $pronunciation, $example, $cefr]) {
            $vocabulary = Vocabulary::updateOrCreate(
                ['normalized_term' => Vocabulary::normalizeTerm($term), 'type' => $type],
                [
                    'term' => $term,
                    'part_of_speech' => $pos,
                    'meaning_en' => $meaningEn,
                    'meaning_bn' => $meaningBn,
                    'pronunciation' => $pronunciation,
                    'example' => $example,
                    'cefr_level' => $cefr,
                    'is_active' => true,
                ]
            );

            $this->linker->linkVocabulary($vocabulary);
        }
    }

    /**
     * The array key is the course's place in the course list.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function courseData(): array
    {
        return [
            [
                'slug' => 'everyday-english-starter',
                'title' => 'Everyday English Starter',
                'description' => 'Short, useful sentences for greetings, introductions and ordering food. Perfect for absolute beginners.',
                'category' => 'daily-life',
                'cefr' => 'A1',
                'difficulty' => 1,
                'chapters' => [
                    [
                        'title' => 'Greetings & Introductions',
                        'description' => 'Say hello and talk about yourself.',
                        'lessons' => [
                            [
                                'title' => 'Saying Hello',
                                'description' => 'The most common greetings.',
                                'minutes' => 5,
                                'sentences' => [
                                    ['en' => 'Hello, how are you?', 'bn' => 'হ্যালো, আপনি কেমন আছেন?'],
                                    ['en' => 'I am fine, thank you.', 'bn' => 'আমি ভালো আছি, ধন্যবাদ।'],
                                    ['en' => 'Good morning, everyone.', 'bn' => 'সবাইকে সুপ্রভাত।'],
                                    ['en' => 'Nice to meet you.', 'bn' => 'আপনার সাথে দেখা হয়ে ভালো লাগলো।'],
                                    ['en' => 'See you tomorrow.', 'bn' => 'কাল দেখা হবে।'],
                                    ['en' => 'Please sit down.', 'bn' => 'দয়া করে বসুন।'],
                                ],
                            ],
                            [
                                'title' => 'Introducing Yourself',
                                'description' => 'Talk about your name, country and job.',
                                'minutes' => 6,
                                'sentences' => [
                                    ['en' => 'My name is Sara.', 'bn' => 'আমার নাম সারা।'],
                                    ['en' => 'I am from Bangladesh.', 'bn' => 'আমি বাংলাদেশ থেকে এসেছি।'],
                                    ['en' => 'I work as a teacher.', 'bn' => 'আমি শিক্ষক হিসেবে কাজ করি।'],
                                    ['en' => 'I live in Dhaka with my family.', 'bn' => 'আমি আমার পরিবারের সাথে ঢাকায় থাকি।'],
                                    ['en' => 'What do you do?', 'bn' => 'আপনি কী করেন?'],
                                    [
                                        'en' => 'Where are you from?',
                                        'bn' => 'আপনি কোথা থেকে এসেছেন?',
                                        'chunks' => [
                                            [ContentType::Word, 'where', 'কোথায়'],
                                            [ContentType::Phrase, 'are you from', 'আপনি কোথা থেকে'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'At the Café',
                        'description' => 'Order food and drinks politely.',
                        'lessons' => [
                            [
                                'title' => 'Ordering Coffee',
                                'description' => 'Polite phrases for ordering and paying.',
                                'minutes' => 7,
                                'sentences' => [
                                    [
                                        'en' => 'I would like a cup of coffee, please.',
                                        'bn' => 'আমি এক কাপ কফি চাই, দয়া করে।',
                                        'note' => '"I would like" is a polite way to say "I want".',
                                        'chunks' => [
                                            [ContentType::Word, 'coffee', 'কফি'],
                                            [ContentType::Word, 'please', 'দয়া করে'],
                                            [ContentType::Phrase, 'I would like', 'আমি চাই'],
                                            [ContentType::Phrase, 'a cup of coffee', 'এক কাপ কফি'],
                                        ],
                                    ],
                                    ['en' => 'Can I have the menu?', 'bn' => 'আমি কি মেনুটা পেতে পারি?'],
                                    ['en' => 'How much is this sandwich?', 'bn' => 'এই স্যান্ডউইচটার দাম কত?'],
                                    ['en' => 'I will have it to go.', 'bn' => 'আমি এটা নিয়ে যাব।', 'note' => '"To go" means takeaway.'],
                                    ['en' => 'Do you accept credit cards?', 'bn' => 'আপনারা কি ক্রেডিট কার্ড নেন?'],
                                    ['en' => 'Keep the change.', 'bn' => 'বাকিটা রেখে দিন।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'travel-english-essentials',
                'title' => 'Travel English Essentials',
                'description' => 'Handle airports and hotels with confidence using longer, natural sentences.',
                'category' => 'travel',
                'cefr' => 'B1',
                'difficulty' => 3,
                'chapters' => [
                    [
                        'title' => 'Airport & Hotel',
                        'description' => 'From check-in to boarding.',
                        'lessons' => [
                            [
                                'title' => 'At the Airport',
                                'description' => 'Check in, ask questions and understand announcements.',
                                'minutes' => 10,
                                'sentences' => [
                                    [
                                        'en' => 'Could you tell me where the check-in counter is?',
                                        'bn' => 'আপনি কি বলতে পারবেন চেক-ইন কাউন্টার কোথায়?',
                                        'chunks' => [
                                            [ContentType::Word, 'counter', 'কাউন্টার'],
                                            [ContentType::Phrase, 'Could you tell me', 'আপনি কি আমাকে বলতে পারবেন'],
                                            [ContentType::Phrase, 'the check-in counter', 'চেক-ইন কাউন্টার'],
                                        ],
                                    ],
                                    ['en' => 'My flight has been delayed by two hours.', 'bn' => 'আমার ফ্লাইট দুই ঘণ্টা দেরি হয়েছে।'],
                                    ['en' => 'I would like a window seat if possible.', 'bn' => 'সম্ভব হলে আমি জানালার পাশের সিট চাই।'],
                                    ['en' => 'Please make sure your luggage is within the weight limit.', 'bn' => 'অনুগ্রহ করে নিশ্চিত করুন আপনার লাগেজ ওজনসীমার মধ্যে আছে।'],
                                    ['en' => 'The boarding gate will close in twenty minutes.', 'bn' => 'বোর্ডিং গেট বিশ মিনিট পরে বন্ধ হয়ে যাবে।'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return list<array<int, string|null>> */
    protected function vocabularyData(): array
    {
        return [
            ['coffee', 'word', 'noun', 'a hot drink made from roasted beans', 'কফি', '/ˈkɒfi/', 'I would like a cup of coffee.', 'A1'],
            ['please', 'word', 'adverb', 'used to make a request polite', 'দয়া করে', '/pliːz/', 'Please sit down.', 'A1'],
            ['menu', 'word', 'noun', 'a list of food and drinks in a restaurant', 'মেনু (খাবারের তালিকা)', '/ˈmenjuː/', 'Can I have the menu?', 'A1'],
            ['teacher', 'word', 'noun', 'a person who teaches', 'শিক্ষক', '/ˈtiːtʃə/', 'I work as a teacher.', 'A1'],
            ['family', 'word', 'noun', 'parents, children and close relatives', 'পরিবার', '/ˈfæməli/', 'I live in Dhaka with my family.', 'A1'],
            ['change', 'word', 'noun', 'money you get back when you pay more than the price', 'খুচরা টাকা (ফেরত টাকা)', '/tʃeɪndʒ/', 'Keep the change.', 'A2'],
            ['to go', 'phrase', null, 'food or drink to take away', 'নিয়ে যাওয়ার জন্য (টেকঅ্যাওয়ে)', null, 'I will have it to go.', 'A2'],
            ['credit card', 'phrase', 'noun', 'a card you use to buy things and pay later', 'ক্রেডিট কার্ড', null, 'Do you accept credit cards?', 'A2'],
            ['delayed', 'word', 'adjective', 'later than the planned time', 'বিলম্বিত (দেরি হওয়া)', '/dɪˈleɪd/', 'My flight has been delayed by two hours.', 'B1'],
            ['luggage', 'word', 'noun', 'bags and suitcases you take when travelling', 'লাগেজ (মালপত্র)', '/ˈlʌɡɪdʒ/', 'Please make sure your luggage is within the weight limit.', 'B1'],
            ['check-in counter', 'phrase', 'noun', 'the desk where you register for a flight', 'চেক-ইন কাউন্টার', null, 'Where is the check-in counter?', 'B1'],
            ['boarding gate', 'phrase', 'noun', 'the place where passengers enter the plane', 'বোর্ডিং গেট', null, 'The boarding gate will close soon.', 'B1'],
            ['window seat', 'phrase', 'noun', 'a seat next to the window', 'জানালার পাশের সিট', null, 'I would like a window seat.', 'B1'],
        ];
    }
}
