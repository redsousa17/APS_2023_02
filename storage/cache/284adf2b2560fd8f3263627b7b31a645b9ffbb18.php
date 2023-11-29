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

<body>
    <div class="container">
        <br>
        <br>
    <div class="row  ">
        <div class="col-sm-12 ">
            <label for="basic-url" class="form-label"> Nome Jogador </label><br>
            <h1><?php echo e($result->nome_jogador); ?></h1>
        </div>
    </div>
    <div class="row  ">
        <div class="col-sm-1">
        <label for="basic-url" class="form-label"> id </label><br>
        <h4><?php echo e($result->id); ?> </h4> 
        </div>
        <div class="col-sm-1">
        <label for="basic-url" class="form-label"> Pais </label><br>
        <h4> <?php echo e($result->pais); ?>   </h4> 
        </div>
        <div class="col-sm-1">
        <label for="basic-url" class="form-label"> Liga </label><br>
        <h4> <?php echo e($result->nome_liga); ?>  </h4> 
        </div>
    </div>

    <div class="row  ">
        <div class="col-sm-3">
        <label for="basic-url" class="form-label"> Numeros de Partidas </label><br>
        <h4><?php echo e($result->numero_partidas); ?> </h4> 
        </div>
        <div class="col-sm-3">
        <label for="basic-url" class="form-label"> Substituição  </label><br>
        <h4> <?php echo e($result->numero_substituicao); ?>   </h4> 
        </div>
        <div class="col-sm-3">
        <label for="basic-url" class="form-label"> Tempo Jogado </label><br>
        <h4> <?php echo e($result->tempo_jogado); ?>  </h4> 
        </div>
    </div>

    <div class="row  ">
        <div class="col-sm-3">
        <label for="basic-url" class="form-label"> Gols Marcados </label><br>
        <h4><?php echo e($result->gols_marcados); ?> </h4> 
        </div>
        <div class="col-sm-3">
        <label for="basic-url" class="form-label"> Meta Esperada  </label><br>
        <h4> <?php echo e($result->meta_esperada); ?>   </h4> 
        </div>
        <div class="col-sm-3">
        <label for="basic-url" class="form-label"> Gols Esperado </label><br>
        <h4> <?php echo e($result->gols_esperado); ?>  </h4> 
        </div>
    </div>
    </div>
   


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>

</html><?php /**PATH C:\laragon\www\trabalho-api\resources\views/jogador.blade.php ENDPATH**/ ?>