<?php
date_default_timezone_set("America/Bogota");
class SiteController extends Controller
{
	public $layout = "//layouts/login";
	/*public function filters()
	{
		return array(
			'accessControl',
			'postOnly + delete',
		);
	}

	public function accessRules()
	{
		return array(
			array(
				'allow',
				'actions' => array('logout', 'login'),
				'users' => array('*'),
				'message' => "No está autorizado, por favor comunicarse con el administrador para solicitar acceso",
			),
			array(
				'allow',
				'actions' => array(),
				'users' => array('?'),
				'message' => "No está autorizado, por favor comunicarse con el administrador para solicitar acceso",
			),
			// or
			/*array(
				'allow',
				'actions' => (isset(Yii::app()->user->array_aceesos) ? Yii::app()->user->array_aceesos : array('logout', 'login', 'index')),
				'users' => array('@'),
				//'expression' => 'Yii::app()->user->activatePostventa==1',
				'message' => "No está autorizado, por favor comunicarse con el administrador para solicitar acceso",
			),
			array(
				'deny', //SIN ACCESO GENERAL                                  
				'actions' => array(),
				'users' => array('*'),
				'message' => "",
				'deniedCallback' => array($this, 'accessError'),
			),
		);
	}*/
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha' => array(
				'class' => 'CCaptchaAction',
				'backColor' => 0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page' => array(
				'class' => 'CViewAction',
			),
		);
	}

	public function accessError()
	{
		$this->actionError();
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		$this->layout = "//layouts/main";
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'
		if (!Yii::app()->user->isGuest) {
			$exists = Tbl_usuarios::model()->find('RowId=:id', [':id' => Yii::app()->user->rowId]);
			if ($exists->UpdatePassword == 1) {
				$this->redirect('resetpassword');
			}
		}
		$this->render('index');
	}

	public function actionResetpassword()
	{
		$model = new ResetLoginForm;
		// if it is ajax validation request
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'reset-login-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
		//echo 11;die;
		// collect user input data
		if (isset($_POST['ResetLoginForm'])) {
			$model->attributes = $_POST['ResetLoginForm'];
			if ($model->validate() && $model->validateNewPassword() && $model->validateCurrentPassword()) {
				$tbl_usuarios = new Tbl_usuarios();
				$update = $tbl_usuarios->resetPassword($model->attributes['confirmpassword']);
				if ($update['Status'] == 200) {
					Yii::app()->user->setFlash('success', 'La contraseña se restableció con éxito.');
					$this->redirect('index');
				}
			}
		}
		$this->render('resetpassword', array('model' => $model));
	}

	/**
	 * This is the action to handle external exceptions.
	 */

	public function actionError()
	{
		$this->layout = "//layouts/admin";
		if ($error = Yii::app()->errorHandler->error) {
			if (Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model = new ContactForm;
		if (isset($_POST['ContactForm'])) {
			$model->attributes = $_POST['ContactForm'];
			if ($model->validate()) {
				$name = '=?UTF-8?B?' . base64_encode($model->name) . '?=';
				$subject = '=?UTF-8?B?' . base64_encode($model->subject) . '?=';
				$headers = "From: $name <{$model->email}>\r\n" .
					"Reply-To: {$model->email}\r\n" .
					"MIME-Version: 1.0\r\n" .
					"Content-Type: text/plain; charset=UTF-8";

				mail(Yii::app()->params['adminEmail'], $subject, $model->body, $headers);
				Yii::app()->user->setFlash('contact', 'Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact', array('model' => $model));
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		if (!Yii::app()->user->isGuest) {
			$this->redirect('index');  //
		}
		$model = new LoginForm;
		// if it is ajax validation request
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if (isset($_POST['LoginForm'])) {
			$model->attributes = $_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if ($model->validate() && $model->login())
				$this->redirect('index');  //
		}
		// display the login form
		$this->render('login', array('model' => $model));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	public function actionPasswordRecovery()
	{
		if (isset($_POST)) {
			$tbl_usuarios = new Tbl_usuarios();
			$data = $tbl_usuarios->passwordRecovery($_POST);
			echo CJSON::encode($data);
		} else {
			echo CJSON::encode(Responses::getError());
		}
	}
}
