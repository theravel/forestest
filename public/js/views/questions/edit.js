require([
	'jquery',
	'components/markdown-editor/markdown-editor',
	'components/markdown-view/markdown-view',
	'tagIt',
], function($, MarkdownEditor, MarkdownView) {
	'use strict';

	var TYPE_SINGLE_LINE = 1,
		TYPE_MULTI_LINE = 2,
		TYPE_RADIOS = 3,
		TYPE_CHECKBOXES = 4,
		MAX_AUTOCOMPLETE_ITEMS = 10;

	var preview = new MarkdownView(),
		editor = new MarkdownEditor(preview),
		jLanguageSelector = $('#question-program-lang'),
		selectedLanguageId = jLanguageSelector.val(),
		selectedLanguageAlias = jLanguageSelector.find('option:selected').data('highlight'),
		categoriesAutocomplete = [],
		jAnswersBlock = $('#question-answers-block'),
		jTypeSelect = $('#question-type'),
		answersType = parseInt(jTypeSelect.val()),
		jAnswersActiveArea = jAnswersBlock.find('#active-answers-' + answersType)
	;


	/*** categories-tags ***/
	var updateCategoriesAutocomplete = function(selectedLanguageId) {
			$.ajax({
				url: '/questions/categories',
				data: {
					language: selectedLanguageId
				},
				dataType: 'json',
				success: function(data) {
					categoriesAutocomplete = data;
				},
				error: function(request, status, error) {
					// @TODO fix
					console.error(request, status, error);
				}
			})
		}
	;

	$('#question-categories').tagit({
		allowSpaces: true,
		fieldName: 'categories[]',
		placeholderText: $('#t-categories-placeholder').text(),
		autocomplete: {
			source: function(request, response) {
				var term = $.trim(request.term.toLowerCase()),
					matches = [];

				for (var i = 0; i < categoriesAutocomplete.length; i++) {
					var name = categoriesAutocomplete[i].toLowerCase();
					if (0 === name.indexOf(term)) {
						matches.push(categoriesAutocomplete[i]);
						if (matches.length === MAX_AUTOCOMPLETE_ITEMS) {
							break;
						}
					}
				}
				response(matches);
			}
		}
	});

	jLanguageSelector.on('change', function() {
		selectedLanguageId = $(this).val();
		selectedLanguageAlias = $(this).find('option:selected').data('highlight');
		preview.setDefaultLanguage(selectedLanguageAlias);
		editor.updateView();
		updateCategoriesAutocomplete(selectedLanguageId);
	});

	preview.setDefaultLanguage(selectedLanguageAlias);
	updateCategoriesAutocomplete(selectedLanguageId);


	/*** answers area ***/
	jTypeSelect.on('change', function() {
		answersType = parseInt($(this).val());
		switch (answersType) {
			case TYPE_RADIOS:
			case TYPE_CHECKBOXES:
				jAnswersBlock.removeClass('hidden');
				jAnswersBlock.find('.active-answers-area').addClass('hidden');
				jAnswersActiveArea = jAnswersBlock.find('#active-answers-' + answersType)
					.removeClass('hidden');
				break;
			case TYPE_SINGLE_LINE:
			case TYPE_MULTI_LINE:
			default:
				jAnswersBlock.addClass('hidden');
				break;
		}
		resetValidationErrors();
	});

	$('.answers-container').on('change', '.correct-switch', function() {
		var jElement = $(this);
		jElement.siblings('.correct-switch-value')
			.val(jElement.prop('checked') ? 1 : 0);
		jElement.siblings('label')
			.toggleClass('glyphicon-remove-circle glyphicon-ok-circle');
		jElement.parent('.answer-correct-toggle')
			.toggleClass('answer-correct-ok answer-correct-wrong');
	});

	$('.answers-container').on('click', '.answer-correct-toggle', function() {
		var jElement = $(this),
			jSwitch = jElement.find('.correct-switch'),
			prevState = jSwitch.prop('checked');
		jSwitch.prop('checked', !prevState).trigger('change');
		if (TYPE_RADIOS === answersType) {
			jAnswersActiveArea.find('.answer-correct-ok .correct-switch')
				.not(jSwitch)
				.prop('checked', false)
				.trigger('change');
		}
	});

	$('.answers-container').on('click', '.answer-remove', function() {
		$(this).parents('.answer-wrapper').remove();
	});

	$('.add-answer').on('click', function() {
		var jTemplate = jAnswersActiveArea.find('.answer-template').clone();
		jTemplate.find('[data-name]').each(function() {
			var jInput = $(this);
			jInput.attr('name', jInput.attr('data-name'))
				.removeAttr('data-name');
		});
		jTemplate.removeClass('answer-template')
			.appendTo(jAnswersActiveArea);
		validationCheck.noAnswers();
	});


	/*** validation and submit ***/
	var jErrorMessages = {
			emptyText: $('#error-empty-text'),
			noAnswers: $('#error-no-answers'),
			emptyAnswer: $('#error-empty-answer'),
			noCorrect: $('#error-no-correct'),
			server: $('#error-server')
		},
		validationCheck = {
			emptyText: function() {
				jErrorMessages.emptyText.toggleClass('hidden', editor.getValue().length > 0);
				return editor.getValue().length > 0;
			},
			noAnswers: function() {
				if ([TYPE_SINGLE_LINE, TYPE_MULTI_LINE].indexOf(answersType) >= 0) {
					jErrorMessages.noAnswers.addClass('hidden');
					return true;
				}
				var answers = jAnswersActiveArea.find('.answer-wrapper:visible');
				jErrorMessages.noAnswers.toggleClass('hidden', answers.length > 1);
				return answers.length > 1;
			},
			emptyAnswer: function() {
				var hasEmpty = false;
				jAnswersActiveArea.find('input[type="text"]:visible').each(function() {
					if (!$.trim($(this).val()).length) {
						hasEmpty = true;
					}
				});
				jErrorMessages.emptyAnswer.toggleClass('hidden', !hasEmpty);
				return !hasEmpty;
			},
			noCorrect: function() {
				if ([TYPE_SINGLE_LINE, TYPE_MULTI_LINE].indexOf(answersType) >= 0) {
					jErrorMessages.noCorrect.addClass('hidden');
					return true;
				}
				var hasFlagged = jAnswersActiveArea.find('input:checked').length > 0;
				jErrorMessages.noCorrect.toggleClass('hidden', hasFlagged);
				return hasFlagged;
			}
		},
		resetValidationErrors = function() {
			$('#validation-errors .alert').addClass('hidden');
		}
	;

	editor.addEventHandler('change', function() {
		if (editor.getValue().length > 0) {
			validationCheck.emptyText();
		}
	});

	$('#question-suggest').on('submit', function() {
		var valid = true,
			pageScroll = function() {
				$('html, body').animate({scrollTop: 0}, 300);
			},
			errorHandler = function() {
				jErrorMessages.server.removeClass('hidden');
				pageScroll();
			};
		
		resetValidationErrors();
		valid &= validationCheck.emptyText();
		valid &= validationCheck.noAnswers();
		valid &= validationCheck.emptyAnswer();
		valid &= validationCheck.noCorrect();
		if (valid) {
			var button = $(this).addClass('processing');
			$.ajax({
				url: jAnswersBlock.data('saveUrl'),
				type: 'POST',
				data: $(this).serialize(),
				success: function(data) {
					if (data.id) {
						window.location = '/questions/preview/' + data.id;
					} else {
						errorHandler();
					}
				},
				error: errorHandler,
				complete: function() {
					button.removeClass('processing');
				}
			});
		} else {
			pageScroll();
		}
		return false;
	});
})