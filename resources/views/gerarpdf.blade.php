<!DOCTYPE html>
<html>
<head>
    <title>Ata da Reunião</title>
</head>
<body>
    <div class="container">
        <div class="titulo">
            ATA DA REUNIÃO
        </div>
        <div class="data">
            Data: {{$data}}
        </div>
        <div class="conteudo">
            <h2>Pauta da Reunião:</h2>
            <ul>
                <li>{{$titulo}}</li>
                <li>{{$detalhamento}}</li>
            </ul>
            <table id="tableAtividades" style="width: 100%">
                <thead style="background-color: lightgray; border-radius: 15px">
                <tr>
                 <th class="align-middle" scope="col">Nome</th>
                 <th>Assinatura</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cinza = '#ddd';
                $branco = '#fff';
                $cor = $branco;
                $ultimaCor = $cor;
                ?>
    
                @foreach($membros as $membro)
                    <tr style="background-color:{{ $cor }}" <?php $ultimaCor = $cor?> >
                        <td class="align-middle" scope="col" style="text-align: center">{{ $membro }}</td>
    
                        
                    </tr>
                   
                @endforeach
            </tbody>
        </table>
            
