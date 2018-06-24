<?php

class SafeAnswer extends BaseModel {
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'safe_answers';

    /**
     * 资源名称
     * @var string
     */
    public static $resourceName = 'SafeAnswers';

    public static $columnForList = [
        'id',
        'username',
        'question',
        'answer',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'id',
        'user_id',
        'username',
        'question_id',
        'question',
        'answer',
        'created_at',
        'updated_at'
    ];
    public static $ignoreColumnsInEdit = [
        'user_id'
    ];
    public static $htmlSelectColumns = [
        'question_id' => 'aQuestions'
    ];
    public static $rules = [
        'user_id' => 'required|integer|min:1',
        'username' => 'required|max:16',
        'question_id'        => 'required|integer',
        'answer'        => 'required|between:0,500',
    ];

    protected function beforeValidate() {
        parent::beforeValidate();
        $oUser = User::findUser($this->username);
        $oQuestion = SafeQuestion::find($this->question_id);
        if($oUser && $oQuestion){
            $this->user_id = $oUser->id;
            $this->question = $oQuestion->content;
            return true;
        }
        return false;
    }

    public $orderColumns = [
        'created_at' => 'desc'
    ];


}
