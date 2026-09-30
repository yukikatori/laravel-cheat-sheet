<?php

// 第1引数の値が第2引数の配列に含まれているかを判定する
// 「ログインユーザーのロールが Student / Coach / Admin のいずれかなら true を返す」 
in_array($auth->role, [UserRole::Student, UserRole::Coach, UserRole::Admin], true);