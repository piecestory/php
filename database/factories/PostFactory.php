<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Content\Models\Post;
use App\Domain\Shared\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return [
            'title_ar' => "مقالة {$n}",
            'title_en' => "Article {$n}",
            'slug_ar' => "مقالة-{$n}",
            'slug_en' => "article-{$n}",
            'excerpt_ar' => 'مقتطف قصير عن المقالة.',
            'excerpt_en' => 'A short excerpt.',
            'body_ar' => "## عنوان فرعي\n\nفقرة عن القطع العتيقة.",
            'body_en' => "## Subheading\n\nA paragraph about antiques.",
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()]);
    }
}
