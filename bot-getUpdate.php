<?php
/**
* 
*/
echo "<pre>";
header('Content-Type: text/html; charset=utf-8');
require 'Sql.php'; // one class for insert update delete select mysql
class BOT extends Sql
{
	public $Token = "724260205:AAE-pvGXbrdTpDTx0lB2bvihLB6vQ_xaBNo";
	public $Url;
	public $Result;
	public $chatId;
	public $Text;
	public $Offset = 1;

	function __construct()
	{
		parent::__construct();
	}


	// function first for start bot AND get all request
	public function Start()
	{
		$LIMIT = $this->Select('informations','','ID','DESC');
		if ($LIMIT)	$this->Offset += $LIMIT['ID'];


		$this->Delete('informations','`Status` = 1');
		$this->Url = "https://api.telegram.org/bot".$this->Token."/getupdates?offset=".$this->Offset; 
		$this->Result = json_decode(file_get_contents($this->Url),true);
		if(!$this->Result['ok'])
			die('Not Connect Bot To API');
		foreach ($this->Result['result'] as $key) 
		{
			$newData['ID'] = $key['update_id'];
			if (isset($key['callback_query']['message'])) 
			{
				$newData['chatId'] = $key['callback_query']['message']['chat']['id'];
				$newData['Text'] = $key['callback_query']['data'];
				$newData['keyBoard'] = 'inline';
			}else
			{
				$newData['chatId'] = $key['message']['chat']['id'];
				$newData['Text'] = $key['message']['text'];
				$newData['keyBoard'] = 'keyboard';
			}
			$this->Insert('informations',$newData);				
		}
		$this->Response();
	}


	// method for response to user 

	private function Response()
	{
		$this->Result = $this->Select('informations','`Status` = 0');
		if ($this->Result) 
		{
			$this->chatId = $this->Result['chatID'];
			$newValue['Status'] = 1;
			$this->Update('informations',$newValue,'`ID` ='.$this->Result['ID']);
			switch ($this->Result['keyBoard']) {
				case 'inline':
				$this->getConditionInline();
				break;
				case 'keyboard':
				$this->getCondition();
				break;
			}
			$this->Response();
		}
	}

	// method for condition inline
	private function getConditionInline()
	{
		$Result = $this->Select('contents','`categoryID` = '.$this->Result['Text'],'Counter');
		if ($Result) 
		{
			$this->Action('upload_photo');
			$this->sendFile($Result['File'],$Result['Text'],$Result['Type'],$Result['Method']);
			$Update['Counter'] = $Result['Counter'] + 1;
			$this->Update('contents',$Update,'`ID` = '.$Result['ID']);
		}else
		{
			$this->Action('typing');
			$this->sendMessage('پیامی یافت نشد');
		}
	}



	// method for condition keyboard
	private function getCondition()
	{

		$arrayCheck = array(
			'startBot' => '/start', 
			'aboutMe' => 'درباره ی من ',
			'getRezome' => 'نمونه کارها '
		);
		$getFunction = array_search($this->Result['Text'],$arrayCheck);
		$this->{$getFunction}();

	}

	// method start 
	private function startBot()
	{
		$this->Text = "گزینه ی مورد نظر را انتخاب فرمایید 🕹";
		$Keyboard = array(
			'keyboard' => array(
				array('درباره ی من 🤓','نمونه کارها 📗')
			),"resize_keyboard" => true,"one_time_keyboard" => true
		);
			$this->Action('typing');
		$this->sendKeyboard($this->Text,$Keyboard);

	}

	// function for get about me
	private function aboutMe()
	{
		$this->Result = $this->Select('abouts');
			$this->Action('typing');
		$this->sendMessage($this->Result['Content']);
	}

    // method for get
    private function getRezome()
    {
    	$this->Result = $this->Selects('files');
    			$Keyboard  = [
			'inline_keyboard'=>[
				[
					['text'=>'بیشتر 🤔','switch_inline_query'=>'@chose'],['text'=>'قسمتی از کد 📋','callback_data'=>'@chose']
				]
			]
		];
    	foreach ($this->Result as $Value)
    	{

			$this->Action('upload_photo');
    		$this->sendFile($Value['File'],substr($Value['Description'],0,100).' ... ','photo','sendPhoto',$Keyboard);
    	}
    }



	// method for send keyboard AND inlineKeyboard
	private function sendKeyboard($Text,$Keys)
	{
		
		$sendKeys= json_encode($Keys);
		$sendMessageUrl = "https://api.telegram.org/bot".urlencode($this->Token)."/sendMessage?chat_id=".urlencode($this->chatId)."&text=".urlencode($Text)."&reply_markup=".urlencode($sendKeys);
		file_get_contents($sendMessageUrl);
	} 
	//method for send message  
	private function sendMessage($Text)
	{
		$sendMessageUrl = "https://api.telegram.org/bot".urlencode($this->Token)."/sendMessage?chat_id=".urlencode($this->chatId)."&text=".urlencode($Text);
		file_get_contents($sendMessageUrl);
	}

	// method for action user
	private function Action($Type)
	{
		$sendMessageUrl = "https://api.telegram.org/bot".urlencode($this->Token)."/sendChatAction?chat_id=".urlencode($this->chatId)."&action=".urlencode($Type);
		file_get_contents($sendMessageUrl);
	}
	private function sendFile($Image,$Caption,$Type,$Mehtod,$Keys)
	{
		$Keys= json_encode($Keys);
		$Url = "https://api.telegram.org/bot".urlencode($this->Token)."/".$Mehtod."?chat_id=".urlencode($this->chatId)."&reply_markup=".urlencode($Keys);
		$post_fields = array(
			'chat_id'   => $this->chatId,
	    	$Type     => new CURLFile(realpath($Image)),
		);
		if ($Type == 'photo' || $Type == 'audio') 
		{
	    	$post_fields['caption'] = $Caption;
		}else
		{
			$post_fields['text'] = $Caption;
		}

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    "Content-Type:multipart/form-data"
		));
		curl_setopt($ch, CURLOPT_URL, $Url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
		$output = curl_exec($ch);
	}

}
?>
