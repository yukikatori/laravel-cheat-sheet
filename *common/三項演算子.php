<?php

// 条件 ? trueの場合 : falseの場合
$updatedAt = $replyCount > 0
    ? $createdAt->copy()->addHours($replyCount + 1)
    : $createdAt->copy();