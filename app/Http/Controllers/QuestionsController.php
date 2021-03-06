<?php namespace Forestest\Http\Controllers;

use Illuminate\Http\Request;
use Input;

use Forestest\Models\Answer;
use Forestest\Models\Question;
use Forestest\Models\Category;
use Forestest\Models\Translation;
use Forestest\Models\ProgramLanguage;
use Forestest\Models\QuestionHierarchy;
use Forestest\Models\Enum\QuestionType;
use Forestest\Models\Enum\ModerationStatus;
use Forestest\Repositories\QuestionsRepository;
use Forestest\Repositories\CategoriesRepository;
use Forestest\Exceptions\ValidationException;
use Forestest\Http\Controllers\Base\BaseController;

class QuestionsController extends BaseController {

	protected $pageCss = [
		'/vendor/components/codemirror-5.0/lib/codemirror.css',
		'/vendor/components/highlight-8.4/styles/default.css',
		'/vendor/components/jquery-ui-1.11.3/jquery-ui.min.css',
		'/vendor/components/tagit-2.0/jquery.tagit.css',
		'/components/markdown-editor/markdown-editor.css',
		'/components/markdown-view/markdown-view.css',
	];

	public function getPreview($id)
	{
		$question = Question::findOrFail($id);
		return view('questions/preview', [
			'language' => Translation::LANGUAGE_DEFAULT,
			'question' => $question,
		]);
	}

	public function getSuggest()
	{
		$this->setJsPath('questions/edit');
		return view('questions/edit', $this->getQuestionViewData());
	}

	public function getEdit($id)
	{
		$question = Question::findOrFail($id);
		return view('questions/edit', $this->getQuestionViewData($question));
	}

	public function postSuggest(Request $request)
	{
		$question = $this->createNewQuestion($request);
		$this->setFlashMessage('questionSuggestSuccess');
		return response()->json(['id' => $question->getId()]);
	}

	public function postEdit($parentId, Request $request)
	{
		$parentQuestion = Question::findOrFail($parentId);
		$question = $this->createNewQuestion($request, $parentQuestion);
		$parentHierarchy = $parentQuestion->getHierarchy();
		$parentHierarchy->addChildId($question->getId());
		$parentHierarchy->save();
		$this->setFlashMessage('questionEditSuccess');
		return response()->json(['id' => $question->getId()]);
	}

	public function getCategories(Request $request)
	{
		$repository = new CategoriesRepository();
		$categories = $repository->getAutocompleteValues($request->get('language'));
		$categoryNames = array_map(function(Category $category) {
			return $category->getName();
		}, iterator_to_array($categories));
		return response()->json($categoryNames);
	}

	private function createNewQuestion(Request $request, Question $parentQuestion = null)
	{
		$question = new Question();
		$question->setType($request->get('questionType'));
		$question->setProgramLanguageId($request->get('programLanguage'));
		$question->setModerationStatus(ModerationStatus::STATUS_PENDING);
		$question->setTranslation(Translation::LANGUAGE_DEFAULT, $request->get('text'));
		$question->setUserId($this->hasUser() ? $this->getUser()->getId() : null);
		$repository = new QuestionsRepository();
		$repository->to($question)
			->attach('hierarchy', $this->prepareHierarchy($parentQuestion))
			->attach('answers', $this->getAnswerToSave($question))
			->attach('categoriesIds', $this->getCategoriesIds($question))
			->save();
		return $question;
	}

	private function getQuestionViewData(Question $question = null)
	{
		return [
			'programLanguages' => ProgramLanguage::allOrdered()->get(),
			'questionTypes' => Question::getTypes(),
			'typesWithoutAnswers' => QuestionType::getTypesWithoutAnswers(),
			'activeQuestionType' => $question ? $question->getType() : QuestionType::DEFAULT_SELECTED,
			'activeProgramLanguageId' => $question ? $question->getProgramLanguageId() : ProgramLanguage::DEFAULT_SELECTED,
			'categories' => $question ? $question->getCategories() : [],
			'answers' => $this->getViewAnswers($question),
			'language' => Translation::LANGUAGE_DEFAULT,
			'question' => $question,
		];
	}

	private function getViewAnswers(Question $question = null)
	{
		$emptyForm = [null, null];
		$result = [
			QuestionType::TYPE_RADIOS => $emptyForm,
			QuestionType::TYPE_CHECKBOXES => $emptyForm,
		];
		if ($question) {
			$result[$question->getType()] = $question->getAnswers();
		}
		return $result;
	}

	private function prepareHierarchy(Question $parentQuestion = null)
	{
		$hierarchy = new QuestionHierarchy;
		if ($parentQuestion) {
			$hierarchy->setParentId($parentQuestion->getId());
		}
		return $hierarchy;
	}

	private function getAnswerToSave(Question $question)
	{
		$result = [];
		if (in_array($question->getType(), QuestionType::getTypesWithoutAnswers())) {
			return $result;
		}
		foreach ($this->getAnswersInput($question) as $index => $answerText) {
			$answer = new Answer();
			$answer->setIsCorrect($this->getAnswersFlagsInput($question, $index));
			$answer->setTranslation(Translation::LANGUAGE_DEFAULT, $answerText);
			$result[] = $answer;
		}
		return $result;
	}

	private function getAnswersInput(Question $question)
	{
		$answers = Input::get('answers');
		$questionType = $question->getType();
		if (!isset($answers[$questionType]) || !is_array($answers[$questionType])) {
			throw new ValidationException('Answers data is invalid');
		}
		return $answers[$questionType];
	}

	private function getAnswersFlagsInput(Question $question, $answerIndex)
	{
		$answersCorrect = Input::get('answersCorrect');
		$questionType = $question->getType();
		if (!isset($answersCorrect[$questionType][$answerIndex])) {
			throw new ValidationException('Answer is flagged neither correct nor incorrect');
		}
		return $answersCorrect[$questionType][$answerIndex];
	}

	private function getCategoriesIds(Question $question)
	{
		$categoryNames = Input::get('categories', []);
		if (!is_array($categoryNames)) {
			throw new ValidationException('Categories have invalid format');
		}
		$repository = new CategoriesRepository();
		return $repository->getOrCreateIds($categoryNames);
	}

}
