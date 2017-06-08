<?php

/**
 * Connected Communities Initiative
 * Copyright (C) 2016 Queensland University of Technology
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace humhub\modules\questionanswer;

use humhub\modules\questionanswer\models\QAComment;
use humhub\modules\questionanswer\models\Tag;
use humhub\modules\questionanswer\widgets\KnowledgeTour;
use Symfony\Component\Config\Definition\Exception\Exception;
use Yii;

use humhub\modules\questionanswer\models\Question;
use humhub\modules\questionanswer\models\Answer;
use humhub\models\Setting;
use humhub\modules\karma\models\Karma;
use humhub\modules\search\engine\Search;

class Events extends \yii\base\Object
{
    /** 
     * Add the Q&A menu item to 
     * the top menu 
     * @param $event
     */
    public static function onTopMenuInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $event->sender->addItem(array(
            'label' => 'Knowledge',
            'url' => \Yii::$app->urlManager->createUrl('/questionanswer/question/index', array()),
            'icon' => '<i class="fa fa-stack-exchange"></i>',
            'isActive' => (\Yii::$app->controller->module && \Yii::$app->controller->module->id == 'questionanswer'),
            'sortOrder' => 10,
        ));
    }


    /**
     * A question has been created
     * @param type $event
     */    
    public static function onQuestionAfterSave($event) 
    {
        $karma = new Karma();
        $karma->addKarma('asked', \Yii::$app->user->id);
    }

    /**
     * On rebuild of the search index, rebuild all space records
     *
     * @param type $event
     */
    public static function onSearchRebuild($event)
    {
        foreach (Question::find()->all() as $obj) {
            Yii::$app->search->add($obj);
        }

        foreach (Answer::find()->all() as $obj) {
            Yii::$app->search->add($obj);
        }

        foreach (Tag::find()->all() as $obj) {
            Yii::$app->search->add($obj);
        }
    }

    /**
     * An answer has been created
     * @param type $event
     */
    public static function onAnswerAfterSave($event) 
    {
        $karma = new Karma();
        $karma->addKarma('answered', \Yii::$app->user->id);
    }


    /**
     * A question has been voted on 
     * This method will determine what type
     * of vote has been cast and what karma to give.
     * 
     * Key Votes:
     * - up vote on question
     * - up vote on answer
     * - marked as best answer
     *
     * @param type $event
     */
    public static function onQuestionVoteAfterSave($event) 
    {
        $karma = new Karma();
        switch($event->sender->vote_type) {
            case "up":
                
                // Only vote on questions and answers
                switch($event->sender->vote_on) {
                    case "question":
                        $karma->addKarma('question_up_vote', $event->sender->created_by);
                    break;

                    case "answer":
                        $karma->addKarma('answer_up_vote', $event->sender->created_by);
                    break;
                }

            break;

            case "accepted_answer":
                $karma->addKarma('accepted_answer', $event->sender->created_by);
            break;

        }        

    }

    public static function onSidebarSpaces($event)
    {
        $event->sender->addWidget(KnowledgeTour::className(), array(), array('sortOrder' => 10));
    }

    public static function onSidebarProfiles($event)
    {
        $event->sender->addWidget(KnowledgeTour::className(), array(), array('sortOrder' => 90));
    }
}
