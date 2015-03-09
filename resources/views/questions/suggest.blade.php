@extends('app')

@section('content')
<div class="container">
	<div class="row">
		<div class="col-md-10 col-md-offset-1">
			<div class="panel panel-default">
				<div class="panel-heading">Suggest a question</div>

				<form class="panel-body" mathod="POST" id="question-suggest">

				<div class="errors-wrapper" id="validation-errors">
					<div class="alert alert-danger hidden" id="error-empty-text">
						Question text cannot be empty
					</div>
					<div class="alert alert-danger hidden" id="error-no-answers">
						There should be at least two answers
					</div>
					<div class="alert alert-danger hidden" id="error-empty-answer">
						Answers cannot be empty
					</div>
					<div class="alert alert-danger hidden" id="error-no-correct">
						At least one answer must be flagged as correct
					</div>
				</div>
				<input type="hidden" name="_token" value="<?=Session::token()?>" />
				<div class="row">
					<div class="col-lg-6">
						<div class="form-group">
							<label for="usr">Answer type</label>
							<select class="form-control" id="question-type" name="question-type">
								<option value="1">Single line text</option>
								<option value="2">Multi-line text</option>
								<option value="3" selected="selected">Pick one</option>
								<option value="4">Check all that apply</option>
							</select>
						</div>
					</div>

					<div class="col-lg-6">
						<div class="form-group">
							<label for="usr">Programming language</label>
							<select class="form-control" id="question-program-lang" name="program-language">
								<option value="java">Java</option>
								<option value="javascript" selected="selected">Javascript</option>
								<option value="ruby">Ruby</option>
								<option value="python">Python</option>
								<option value="perl">Perl</option>
								<option value="php">PHP</option>
								<option value="cs">C#</option>
								<option value="cpp">C++</option>
							</select>
						</div>
					</div>
				</div>

				<div class="form-group question-categories">
					<label>Categories</label>
					<span class="hidden" id="t-categories-placeholder">E.g. OOP, Algorithms, Patterns</span>
					<ul id="question-categories" name="categories">
					</ul>
				</div>

				<div class="panel panel-default editor-panel" id="editor-area">
					<div class="panel-heading">
						<span class="question-text">Question text</span>
						<div class="btn-toolbar pull-right" role="toolbar">
							<div class="btn-group" role="group">
								<button id="editor-preview-btn"
										title="Preview mode"
										type="button"
									class="btn btn-default glyphicon glyphicon-eye-open">
								</button>
								<button id="editor-edit-btn"
										title="Edit mode"
										type="button"
									class="btn btn-default glyphicon glyphicon-edit hidden">
								</button>
								<a id="editor-help-btn" href="https://guides.github.com/features/mastering-markdown/" target="_blank" title="Syntax help" class="btn btn-default glyphicon glyphicon-question-sign"></a>
							</div>
							<div class="btn-group" role="group">
								<button id="enable-fullscreen-btn"
										title="Fullscreen mode"
										type="button"
									class="btn btn-default glyphicon glyphicon-fullscreen">
								</button>
							</div>
							<div class="btn-group" role="group">
								<button id="exit-fullscreen-btn"
										title="Exit fullscreen"
										type="button"
									class="btn btn-default glyphicon glyphicon-resize-small hidden">
								</button>
							</div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="panel-body">
						<div id="editor-input">
<textarea id="code" name="text">
Code example:

	function test(arg) {
		console.log(arg, 42, 'string');
	}

Question text...
</textarea>
								<div class="editor-hints">
									Use <a href="https://guides.github.com/features/mastering-markdown/">Markdown</a> 
									syntax and indent source code for highlighting
								</div>
						</div>
						<div id="markdown-view" class="hidden">
						</div>
					</div>
				</div>

				<div class="panel panel-default" id="question-answers-block">
					<div class="panel-heading">
						<span>Answers</span>
					</div>
					<div class="panel-body answers-container">
						<button class="btn btn-default add-answer"
								type="button">
							Add new answer
						</button>
						<div class="flag-correct-hint pull-right">
							Flag correct answers with
							<span class="glyphicon glyphicon-ok-circle answer-correct-ok"></span>
						</div>
						<div class="clear"></div>
						<?php foreach([3 => 'radio', 4 => 'checkbox'] as $typeId => $inputType) { ?>
							<div id="active-answers-<?=$typeId?>" class="active-answers-area <?= ($activeAnswerType === $typeId) ?: 'hidden'; ?>">
								<?php foreach(['answer-template', '', ''] as $index => $className) { ?>
									<div class="input-group answer-wrapper <?=$className?>">
										<span class="input-group-addon answer-correct-toggle answer-correct-wrong">
											<label class="glyphicon glyphicon-remove-circle"></label>
											<input type="<?=$inputType?>"
												   class="correct-switch"
												   <?= (0 === $index) ? 'data-name' : 'name'; ?>="answersCorrect[<?=$typeId?>][]" />
										</span>
										<input type="text" class="form-control"
											   <?= (0 === $index) ? 'data-name' : 'name'; ?>="answers[<?=$typeId?>][]" />
										<span class="input-group-btn">
											<button class="btn btn-default answer-remove" type="button">
												Remove
											</button>
										</span>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>

				<button type="submit" class="btn btn-primary pull-right">
					Submit new question
				</button>
			</form>
		</div>
	</div>
</div>
@endsection
