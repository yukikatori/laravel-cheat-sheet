<?php

return [
    'required' => ':attributeを入力してください。',
    'email' => ':attributeは有効なメールアドレス形式で入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'confirmed' => ':attributeと確認用の入力が一致しません。',
    'unique' => 'この:attributeはすでに登録されています。',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
    ],

    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
    ],
];
