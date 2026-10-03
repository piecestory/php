<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Shared\Concerns\RecordsChanges;
use App\Support\Localization\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question_ar', 'question_en', 'answer_ar', 'answer_en', 'sort_order', 'is_active'])]
class Faq extends Model
{
    use HasTranslations, RecordsChanges;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
