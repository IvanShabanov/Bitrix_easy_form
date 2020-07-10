<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Loader,
	Bitrix\Main\Text\Encoding,
	Bitrix\Iblock,	
	Bitrix\Main\Application,
	Bitrix\Main\Mail\Event,
	Bitrix\Main\Localization\Loc;
global $APPLICATION;
global $USER;
if(!Loader::includeModule("iblock")) {
    return;
};

/*
$APPLICATION->IncludeComponent(
	"GI:easy_form",
	"",
    array (
        'IBLOCK_ID' => '',                      //IBLOCK Куда сохраниться информация с формы]
        'NEW_ELEMENT' => array(                 //Массив Нового элемента в инфоблоке (в каком виде будет сохраняться информация)
            'NAME' => '%name% %phone%',
            'PREVIEW_TEXT' => '%FIELDS%',
            'DETAIL_TEXT' => '%FIELDS%',
            'ACTIVE' => 'Y',
        ),
        'EMAIL_EVENT' => 'FORM_send',      //Событие отправки email сообщения
        'EMAIL_FIELDS' => array(                            //Поля этого события
            'FIELDS' => '%FIELDS%',
        ),
        'SMS_EVENT' => 'FORM_send',      //Событие отправки SMS сообщения
        'SMS_FIELDS' => array(     
            'PHONE_NUMBER' => '%phone%',                       //Поля этого события
            'FIELDS' => '%FIELDS%',
        ),        
        'FORM_ID' => '',                                    //ID формы, Лучше оставьте пустым (само сгенериться)
        'CLASS'=> 'CSSCLASS',
        'TITLE' => 'Form Title',                            //Заголовок формы
        'DESCRIPTION' => 'DESCRIPTION',                     //Текст после заголовка формы
        'DESCRIPTION_AFTER' => 'DESCRIPTION_AFTER',         //Текст после заголовка формы
        'SHOW_REQUERED_TEXT' => 'Y',                        //Показывать сообщение об обязательных полях
        'RECAPTCHA_SITE_KEY' => '',                         //Если хотите использовать рекаптчу заполните
        'RECAPTCHA_SECRET_KEY' => '',
        'FIELDS' => array (                                 //Поля формы
                        array(
                            'type' => 'html',
                            'html' => '<img src="people.png">',
                        ),            
                        array(
                            'type' => 'text',
                            'name' => 'name',
                            'title' => 'Ваше имя',
                            'before' => '<img src="people.png">',
                            'after' => '<img src="people.png">',
                        ),
                        array(
                            'type' => 'text',
                            'name' => 'phone',
                            'title' => 'Номер телефона',
                            'required' => 'required'
                        ),
                        array(
                            'type' => 'select',
                            'name' => 'typeoforder',
                            'title' => 'Тип заявки',
                            'values' => array('Покупка', 'Письмо'),
                        ),    
                        array(
                            'type' => 'text',
                            'name' => 'extra',
                            'title' => 'Тип заявки',
                            'default_value' => 'Письмо',
                            'extra' => 'readonly="readonly"',
                        ),    
                        array(
                            'type' => 'textarea',
                            'name' => 'fulltext',
                            'title' => 'Сообщение',
                        ),                  
                        array(
                            'type' => 'checkbox',
                            'name' => 'agriements',
                            'title' => 'Согласен на обработку персональных данных',
                        ),                          
                    ),
        'SUCCESS_TEXT' => 'Ваше сообщение отправлено',                  //Текст показываемый пользователю при успешной отправке
        'DEBUG' => 'N',
    )
);
*/

if ($arParams['FORM_ID'] == '') {
    $arParams['FORM_ID'] = md5(print_r($arParams, true));
};

if (!function_exists('SetEasyNoCaptcha')) { 
    include_once('EasyNoCaptcha.php');
    if (($arParams['RECAPTCHA_SITE_KEY'] != '') && ($arParams['RECAPTCHA_SECRET_KEY'] != '')) {
        AddGoogleRecaptcha($arParams['RECAPTCHA_SITE_KEY'], $arParams['RECAPTCHA_SECRET_KEY']);
    }
};





/************************************** */
/************************************** */
/************************************** */
if (!class_exists('axi_form_component')) {
    class axi_form_component {
        /************************************* */
        function message($text, $class) {
            return '<p class="'.$class.'">'.$text.'</p>';
        }
        /************************************* */
        function ReplaceInArray($strFind, $strSet, $arResult) {
            if (is_array($arResult)) {
                foreach ($arResult as $key=>$val) {
                    $arResult[$key] = $this->ReplaceInArray($strFind, $strSet, $val);
                }
            } else {
                return str_replace($strFind, $strSet, $arResult);
            }
            return $arResult;
        }
        /**********************************************/
        function Save($arParams) {
            $resultErrors = array();
            $message = '';
            $arResult = $arParams;
            
            foreach ($arParams['FIELDS'] as $field) {
                if ($field['type'] != 'file') {
                    $_REQUEST[$field['name']] = strip_tags($_REQUEST[$field['name']]);
                    $message .= '<p>'.$field['title'].': '.$_REQUEST[$field['name']].'</p>';
                    $strFind = '%'.$field['name'].'%';
                    $strSet = $_REQUEST[$field['name']];
                    $arResult = $this->ReplaceInArray($strFind, $strSet, $arResult);

                    if (($field['required'] != '') && ($_REQUEST[$field['name']] == '')) {
                        $resultErrors[$field['name']] = 'Поле "'.$field['title'].'" не заполнено';
                    };
                } else {
                    $uploaded = $this->SimpleUpload($field['name'], $_SERVER["DOCUMENT_ROOT"].'/upload/');
                    if (count($uploaded['errors']) == 0) {
                        $uploaded_str = '';
                        foreach ($uploaded as $uploaded_val) {
                            $uploaded_link = '//'.$_SERVER['HTTP_HOST'].'/upload/'.$uploaded_val['uploaded'];
                            if ($uploaded_str != '') {
                                $uploaded_str .= ', ';
                            }
                            $uploaded_str .= '<a href="'.$uploaded_link.'">'.$uploaded_link.'</a>';
                        };
                        $message .= '<p>'.$field['title'].': '.$uploaded_str.'</p>';
                        $strFind = '%'.$field['name'].'%';
                        $strSet = $uploaded_str;
                        $arResult = $this->ReplaceInArray($strFind, $strSet, $arResult);
                    } else {
                        $resultErrors[$field['name']] = 'Ощибка загрузки файла';
                    }
                    if (($field['required'] != '') && (count($uploaded) == 0)) {
                        $resultErrors[$field['name']] = 'Поле "'.$field['title'].'" не заполнено';
                    };
                }
            };
            $strFind = '%FIELDS%';
            $strSet = $message;
            $arResult = $this->ReplaceInArray($strFind, $strSet, $arResult);

            if (count($resultErrors) == 0) {
                if (is_numeric($arParams['IBLOCK_ID'])) { 
                    $arLoadProductArray = $arResult['NEW_ELEMENT'];
                    $arLoadProductArray_DEFAULT = array(
                        "MODIFIED_BY"    => 1,  
                        "IBLOCK_SECTION_ID" => false, 
                        "IBLOCK_ID"      => $arParams['IBLOCK_ID'],
                        "NAME"           => 'FORM RESULT '.date('Y-m-d H:i:s'),
                        "ACTIVE"         => "Y",
                        'PREVIEW_TEXT'   => $message,
                        'DETAIL_TEXT'    => $message,                    
                    );
                    foreach ($arLoadProductArray_DEFAULT as $key=>$val) {
                        if (empty($arLoadProductArray[$key])) {
                            $arLoadProductArray[$key] = $val;
                        }
                    }
                    $el = new CIBlockElement;
                    if (!$el->Add($arLoadProductArray)){
                        echo $this->message('Ошибка Элемент не сохранен', 'error');
                    }
                };
                if ($arParams['EMAIL_EVENT'] != '') { 
                    $arFields = $arResult['EMAIL_FIELDS'];
                    if ($arParams['DEBUG'] == 'Y') {
                        echo $this->message('MAIL_EVENT:'.$arParams['EMAIL_EVENT']);
                        echo $this->message('EMAIL:'.print_r($arFields, true));
                    } else {
                        CEvent::SendImmediate($arParams['EMAIL_EVENT'], SITE_ID, $arFields, 'Y', '');
                    }
                }
                if ($arParams['SMS_EVENT'] != '') { 
                    $arFields = $arResult['SMS_FIELDS'];
                    if ($arParams['DEBUG'] == 'Y') {
                        echo $this->message('SMS_EVENT:'.$arParams['EMAIL_EVENT']);
                        echo $this->message('SMS:'.print_r($arFields, true));
                    } else {
                        $sms = new \Bitrix\Main\Sms\Event(
                            $arParams['SMS_EVENT'], 
                            $arFields
                        );
                        $sms->send(true);
                    }
                }

            };
            return $resultErrors;
        }




        /*********************************************** */
        /* SimpleUpload */
        /* Return   
        array(
            [] => array(
                'original' - original filename
                'uploaded' - uploaded filename
                'full_path' - full path to uploaded file
            )
            ['errors'] => array(
                'Text of error'
            )
        )
        */
        /* $fieldname - input name */
        /* $path - upload directory */
        /* $savenames - false/true save origin file names */
        /* $avalable_extensions = array () */
        function SimpleUpload($fieldname, $path, $savenames = false, $avalable_extensions = null) {
            $Result = array();
            if (substr($path, 0, -1) != '/') {
                $path .= '/';
            };
            if (is_array($_FILES[$fieldname]["tmp_name"])) {
                foreach ($_FILES[$fieldname]["tmp_name"] as $key => $value) {
                    if (!empty($_FILES[$fieldname]["tmp_name"][$key])) {
                        $upload_this = true;
                        $file = array();
                        $file['original'] = basename($_FILES[$fieldname]["name"][$key]);
                        $file['uploaded'] = $file['original'];
                        $extension = explode(".", $file['original']);
                        $extension = end($extension);
                        $extension = mb_strtolower($extension);
                        if (!$savenames) {
                            $hash = substr(md5(uniqid(microtime())), 1, 16);
                            $file['uploaded'] = $hash.'.'.$extension;
                        }
                        $file['full_path'] = $path.$file['uploaded'];
                        if (!is_null($avalable_extensions)) {
                            if (!is_array($avalable_extensions)) {
                                $avalable_extensions = explode(',', $avalable_extensions);
                            };
                            if (is_array($avalable_extensions)) {
                                if (!in_array($extension, $avalable_extensions)) {
                                    $upload_this = false;
                                    $Result['errors'][] = 'Not avalable extension file: '.$file['original'];
                                }
                            }
                        };
                        if ($upload_this) {
                            $tmp_name = $_FILES[$fieldname]["tmp_name"][$key];
                            $isloaded = true;
                            if (!move_uploaded_file($tmp_name, $file['full_path'])) {
                                if (!copy($tmp_name, $file['full_path'])) {
                                    $isloaded = false;
                                }
                            }
                            if ($isloaded) {
                                $Result[] = $file;
                            } else {
                                $Result['errors'][] = 'Error to load: '.$file['original'];
                            };
                        };
                    };
                };
            } else {
                if (!empty($_FILES[$fieldname]["tmp_name"])) {
                    $upload_this = true;
                    $file = array();
                    $file['original'] = basename($_FILES[$fieldname]["name"]);
                    $file['uploaded'] = $file['original'];
                    $extension = explode(".", $file['original']);
                    $extension = end($extension);
                    $extension = mb_strtolower($extension);
                    if (!$savenames) {
                        $hash = substr(md5(uniqid(microtime())), 1, 16);
                        $file['uploaded'] = $hash.'.'.$extension;
                    }
                    $file['full_path'] = $path.$file['uploaded'];
                    if (!is_null($avalable_extensions)) {
                        if (!is_array($avalable_extensions)) {
                            $avalable_extensions = explode(',', $avalable_extensions);
                        };
                        if (is_array($avalable_extensions)) {
                            if (!in_array($extension, $avalable_extensions)) {
                                $upload_this = false;
                                $Result['errors'][] = 'Not avalable extension file: '.$file['original'];
                            }
                        }
                    };
                    if ($upload_this) {
                        $tmp_name = $_FILES[$fieldname]["tmp_name"];
                        $isloaded = true;
                        if (!move_uploaded_file($tmp_name, $file['full_path'])) {
                            if (!copy($tmp_name, $file['full_path'])) {
                                $isloaded = false;
                            }
                        }
                        if ($isloaded) {
                            $Result[] = $file;
                        } else {
                            $Result['errors'][] = 'Error to load: '.$file['original'];
                        };
                    };
                };            
            };            
            return $Result;
        }   

        /***********************************************/
        function field_div($field, $type, $inner) {
            $result = '';
            if ($field['error'] != '') {
                $add_class = 'error';
            }
            $result .= '<div class="field '.$type.' '.$field['name'].' '.$add_class.'" >';
            $result .= $field['before'];
            $result .= $inner;
            $result .= $field['after'];
            if ($field['error'] != '') {
                $result .= '<div class="error_text" >';
                $result .= $field['error'];
                $result .= '</div>';
            }
            $result .= '</div>';
            return $result;
        }  
        /***********************************************/
        function field_input($field, $type) {
            $result = '';
            if ($type == 'textinput') {
                $type ='text';
            }
            $required = '';
            if ($field['required'] != '') {
                $required = ' required = "required" ';
                $field['title'] .= '*';
            }
            $result .= '<label for="'.$field['name'].$field['id'].'">'.$field['title'].'</label>';
            $result .= '<input id="'.$field['name'].$field['id'].'" type="'.$type.'" name="'.$field['name'].'" placeholder="'.$field['~title'].'" title="'.$field['~title'].'" value="'.$_REQUEST[$field['name']].'" '.$required.' '.$field['extra'].'/>';
            $result = $this->field_div($field, $type, $result);
            return $result;
        }
        /***********************************************/
        function field_textarea($field) {
            $result = '';
            $required = '';
            if ($field['required'] != '') {
                $required = ' required = "required" ';
                $field['title'] .= '*';
            }
            
            $result .= '<label for="'.$field['name'].$field['id'].'">'.$field['title'].'</label>';
            $result .= '<textarea id="'.$field['name'].$field['id'].'" name="'.$field['name'].'" placeholder="'.$field['~title'].'" title="'.$field['~title'].'" value="'.$_REQUEST[$field['name']].'" '.$required.' '.$field['extra'].'></textarea>';
            $result = $this->field_div($field, 'textarea', $result);

            return $result;
        }
        /***********************************************/
        function field_checkbox($field) {
            $result = '';
            $checked = '';
            if ($field['value'] == '') {
                $field['value'] = '1';
            }
            
            if ($_REQUEST[$field['name']] == $field['value']) {
                $checked = ' checked="checked" ';
            };
            
            $required = '';
            if ($field['required'] != '') {
                $required = ' required = "required" ';
                $field['title'] .= '*';
            };
            $result .= '<input id="'.$field['name'].$field['id'].'" type="checkbox"  name="'.$field['name'].'" value="'.$field['value'].'" title="'.$field['~title'].'" '.$checked.' '.$required.' '.$field['extra'].'/>';
            $result .= '<label for="'.$field['name'].$field['id'].'">'.$field['title'].'</label>';
            
            $result = $this->field_div($field, 'checkbox', $result);
            
            return $result;
        }
        /***********************************************/
        function field_select($field) {
            $result = '';
            $required = '';
            if ($field['required'] != '') {
                $required = ' required = "required" ';
                $field['title'] .= '*';
            }    
            $result .= '<label for="'.$field['name'].$field['id'].'">'.$field['title'].'</label>';
            $result .= '<select id="'.$field['name'].$field['id'].'" name="'.$field['name'].'" title="'.$field['~title'].'" '.$required.' '.$field['extra'].'>';
            if (is_array($field['values'])) {
                foreach ($field['values'] as $val){
                    $selected = '';
                    if ($_REQUEST[$field['name']] == $val) {
                        $selected = ' selected="selected" ';
                    }
                    $result .= '<option value="'.$val.'" '.$selected.'>'.$val.'</option>';
                }
            }
            $result .= '</select>';
            $result = $this->field_div($field, 'select', $result);

            return $result;
        }  
        /***********************************************/
        function field_file($field) {
            $result = '';
            $required = '';
            if ($field['required'] != '') {
                $required = ' required = "required" ';
                $field['title'] .= '*';
            };
            $result .= '<label for="'.$field['name'].$field['id'].'">'.$field['title'].'</label>';
            $result .= '<input id="'.$field['name'].$field['id'].'" type="file" name="'.$field['name'].'" title="'.$field['~title'].'" '.$required.' '.$field['extra'].'/>';
            $result = $this->field_div($field, $type, $result);
            return $result;
        }        
        /***********************************************/
        function field_submit($field) {
            $result = '';
            $result .= '<input id="'.$field['name'].'" type="submit"  name="'.$field['name'].'" value="'.$field['title'].'" title="'.$field['title'].' '.$field['extra'].'"/>';
            $result = $this->field_div($field, 'submit', $result);
            return $result;
        }
        /***********************************************/
        function field_hidden($field) {
            $result = '';
            $result .= '<input id="'.$field['name'].'" type="hidden"  name="'.$field['name'].'" value="'.$field['value'].'"/>';
            return $result;
        }        
        /*********************************************** */


        function Autorun ($arParams) {
            $have_submit = false;
            $fields = array();
            if (is_array($arParams["FIELDS"])) {
                foreach ($arParams["FIELDS"] as $key => $val) {
                    if (is_array($val)) {
                        $field = $val;
                    } else {
                        $field = array(
                            'type' => 'text',
                            'title' => $val,
                            'name' => $key,
                        );                
                    }
                    if ($field['type'] == 'submit') {
                        $have_submit = true;
                    }
                    if (($field['type'] != '') && ($field['name']!='') && ($field['title']!='')) {
                        $fields[] = $field;
                    };
                };
            };
            if (!$have_submit) {
                /* Add submit button */
                $fields[] = array('type' => 'submit',
                                'title' => 'Отправить',
                                'name' => 'submit',
                );
            };
            $arParams["FIELDS"] = $fields;
            $this->arrParams = $arParams;
            $arResult = $arParams;
            $showForm = true;
            if ($_REQUEST['form_id'] == $arParams['FORM_ID']) {
                if (CheckEasyNoCaptha ()) {
                    $errors = $this->Save($arParams);
                } else {
                    $errors['captcha'] = 'Защита от автоматического заполения не пройдена';
                }
                if (count($errors) == 0) {
                    $showForm = false;
                    $arResult['SUCCESS'] = 'Y';
                }
                $arParams['ERRORS'] = $errors;
                $arResult['ERRORS'] = $errors;

            } 
            if ($showForm) {
                $arResult['FORM_START'] = '<div id= "div_formid_'.$arParams['FORM_ID'].'" class="div_form_module formid_'.$arParams['FORM_ID'].'">';
                $arResult['FORM_START'] .='<form id="formid_'.$arParams['FORM_ID'].'" class="form_module formid_'.$arParams['FORM_ID'].'" action="'.$arParams['ACTION'].'" method="post" enctype="multipart/form-data">';
                $arResult['FORM_START'] .='<input type="hidden" name="form_id" value="'.$arParams['FORM_ID'].'">';
                $arResult['FORM_END'] = '</form></div>';
                $arResult['FORM_END'] .= SetEasyNoCaptcha(3, '#formid_'.$arParams['FORM_ID']);
                $have_required = false;
                foreach ($arResult['FIELDS'] as $key=>$field) {
                    if ($errors[$field['name']] != '') {
                        $field['error'] = $arResult['ERRORS'][$field['name']];
                    };
                    if ($field['required'] != '') {
                        $have_required = true;
                    }
                    $field['~title'] = trim(strip_tags($field['title']));
                    $field['type'] = trim(strip_tags($field['type']));
                    $field['name'] = trim(strip_tags($field['name']));
                    if ($_REQUEST[$field['name']] == '' && $field['default_value'] != '') {
                        $_REQUEST[$field['name']] = $field['default_value'];
                    }
                    if (in_array($field['type'], array('text','textinput', 'date', 'tel', 'email'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_input($field, $field['type'] );
                    } else if (in_array($field['type'], array('textarea', 'richedit'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_textarea($field);
                    } else if (in_array($field['type'], array('checkbox' , 'yes_no'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_checkbox($field);
                    } else if (in_array($field['type'], array('select', 'select_element'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_select($field);
                    } else if (in_array($field['type'], array('file'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_file($field);
                    } else if (in_array($field['type'], array('submit'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_submit($field);
                    } else if (in_array($field['type'], array('hidden'))) {
                        $arResult['FIELDS'][$key]['html'] = $this->field_hidden($field);
                    } else if (in_array($field['type'], array('html'))) {
                        $arResult['FIELDS'][$key]['html'] = $field['html'];
                    }
                }
                if (($have_required) && ($form['show_requred_text'] != 'N')) {
                    $arResult['REQURED_TEXT_WARNING'] = '<p class="requred_text">* - обозначены поля обязательные для заполнения<p>';
                };

            };
            return $arResult;
            
        }
    }
};

$form = new axi_form_component();
$arResult = $form->Autorun ($arParams);
$this->IncludeComponentTemplate();
?>
