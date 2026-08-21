<?php

declare(strict_types=1);

namespace Glpi\Form\QuestionType;

use Glpi\Form\Question;

/**
 * Contract for question types that can be rendered inside a composite question.
 *
 * The parent question type remains responsible for storing the child
 * question's extra data and for supplying unique input names.
 */
interface QuestionTypeEmbeddableInterface extends QuestionTypeInterface
{
    /**
     * Render the administration controls using an input-name prefix supplied
     * by the parent question type.
     */
    public function renderEmbeddedAdministrationTemplate(
        ?Question $question,
        string $extra_data_input_prefix,
    ): string;

    /**
     * Render the end-user control using an input name supplied by the parent
     * question type.
     */
    public function renderEmbeddedEndUserTemplate(
        Question $question,
        string $input_name,
    ): string;
}
