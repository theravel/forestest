ALTER TABLE questions
	ADD COLUMN parent_question_ids integer[];

DROP TABLE IF EXISTS questions_hierarchy;