# Bitrix_easy_form


Компонент форм со скрытой каптчей (+ можно подключить Google Recaptcha)





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