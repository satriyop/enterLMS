<?php

use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuestionImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Question Bank Routes
|--------------------------------------------------------------------------
|
| Routes for managing the question bank and importing questions to assessments.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Question Bank CRUD
    Route::resource('question-bank', QuestionBankController::class);

    // Question Bank Actions
    Route::post('question-bank/{question_bank}/duplicate', [QuestionBankController::class, 'duplicate'])
        ->name('question-bank.duplicate');

    // Import Questions to Assessment
    Route::get('courses/{course}/assessments/{assessment}/import-questions', [QuestionImportController::class, 'index'])
        ->name('assessments.import-questions.index');
    Route::post('courses/{course}/assessments/{assessment}/import-questions', [QuestionImportController::class, 'store'])
        ->name('assessments.import-questions.store');
});
