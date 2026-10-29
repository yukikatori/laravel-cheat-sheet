tasks_countは、そのカテゴリに紐づいているタスクの件数です。

コントローラーで書いた次の処理によって、取得したカテゴリに追加されます。

->withCount('tasks')

withCount()は、リレーション名 + _count という名前で件数を持たせます。

$category->tasks_count; // 紐づいているタスクが2件なら「2」

DBにtasks_countというカラムがあるわけではなく、取得時に付けられる値です。

今回のテストでは、

$this->assertSame(2, $categories->first()->tasks_count);

で「最初のカテゴリに紐づくタスク数が、整数の2である」と確認しています。