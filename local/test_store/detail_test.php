<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài thi</title>
    <style>
        *{ font-family: DejaVu Sans;}
        

        body {
            margin: 0 50px;
        }
        table, td, th {
            border: 1px solid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            width: 30%;
        }

        .content {
            margin-top: 8px;
        }

        .answer {
            margin-left: 5px;
            text-align: justify;
        }
        .r0 {
            position: relative;
        }
        .icon {
            position: absolute;
        }
        input[type="radio"] {
            margin: 0;
            padding: 0;
        }

        label {
            margin: 0;
            padding: 0;
        }

        input[type="radio"],
        label, span {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <h1>Bài thi: <?=$name?></h1>
    <table class="table table-bordered table-sm">  
        <tbody>
            <tr>
                <th class="cell" scope="row">
                    <span class="userinitials size-35">Họ và tên</span>
                </th>
                <td class="cell">
                    <?=$fname?> <?=$lname?>
                </td>
            </tr>
            <tr>
                <th class="cell" scope="row">Bắt đầu</th>
                <td class="cell">
                    <?=$tstart?>
                </td>
            </tr>
            <tr>
                <th class="cell" scope="row">Trạng thái</th>
                <td class="cell"><?=$state_info?></td>
            </tr>
            <tr>
                <th class="cell" scope="row">Kết thúc</th>
                <td class="cell">
                    <?=$tfinish?>
                </td>
            </tr>
            <tr>
                <th class="cell" scope="row">Tổng thời gian</th>
                <td class="cell"><?=$timesum?></td>
            </tr>
            <tr>
                <th class="cell" scope="row">Điểm</th>
                <td class="cell"><?=$sumgradesqa?>/<?=$sumgradesq?></td>
            </tr>
            <tr>
                <th class="cell" scope="row">Điểm 10</th>
                <td class="cell"><b><?=$rawgrade?></b> out of <?=$grade?> (<b><?=$percent?></b>%)</td>
            </tr>
        </tbody>
    </table>
    <!-- ---------- -->
    <?php foreach ($q_info as $key => $info): ?>
    <div class="content">
        <div class="qtext"><b>Câu <?=$key+1?>: <?=$info['qtext']?></b></div>
            <div class="answer">
                <?php foreach ($info['aa'] as $answer): ?>
                <div class="r0">
                    <span class="icon"><?= $answer['icon_check'] ?></span>
                    <input class="tt" type="radio" disabled="disabled" <?= $answer['checked'] ?> style="margin-left: 20px; <?= $answer['appearance'] ?>">
                    <?= $answer['solid_circle'] ?>
                    <label class="ml"><?= $answer['a'] ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>