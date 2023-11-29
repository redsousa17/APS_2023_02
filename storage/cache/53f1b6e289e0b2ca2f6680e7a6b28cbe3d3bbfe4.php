<?php
ob_start();
session_start();
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabalho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<?php echo e(dd($itens)); ?>

<body>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Pais</th>
                <th scope="col">Nome da Liga</th>
                <th scope="col">Clube</th>
                <th scope="col">Nome Jogador</th>
                <th scope="col">partidas</th>
                <th scope="col">Substituicao</th>
                <th scope="col">Tempo jogado</th>
                <th scope="col">Gols marcados</th>
                <th scope="col">Meta esperada</th>
                <th scope="col">Gols esperado</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $itens['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <th scope="row"><?php echo e($data->id); ?></th>
                <td><?php echo e($data->pais); ?></td>
                <td><?php echo e($data->nome_liga); ?></td>
                <td><?php echo e($data->clube); ?></td>
                <td><?php echo e($data->nome_jogador); ?></td>
                <td><?php echo e($data->numero_partidas); ?></td>
                <td><?php echo e($data->numero_substituicao); ?></td>
                <td><?php echo e($data->tempo_jogado); ?></td>
                <td><?php echo e($data->gols_marcados); ?></td>
                <td><?php echo e($data->meta_esperada); ?></td>
                <td><?php echo e($data->gols_esperado); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="row  gy-2 gx-3 align-items-center">
        <div class="col-sm-12 d-md-flex">
            <nav aria-label="...">
                <ul class="pagination">
                    <?php $__currentLoopData = $itens['links']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($link['active'] == "False"): ?>
                            <li class="page-item active">
                                <a class="page-link" href="/home<?php echo e($link['url']); ?>"><?php echo e($link['label']); ?></a>
                            </li>
                        <?php else: ?>
                            <li class="page-item">
                                <a class="page-link" href="/home<?php echo e($link['url']); ?>"><?php echo e($link['label']); ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </nav>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html><?php /**PATH C:\laragon\www\trabalho-api\resources\views/home.blade.php ENDPATH**/ ?>