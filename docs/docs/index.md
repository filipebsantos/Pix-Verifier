!["Pix Verifier"](img/pixverifier_logo.png)

# **Introdução**

O Pix Verifier nasceu para suprir uma necessidade da drogaria dos meus pais. Eu precisava que os colaboradores pudessem acompanhar as transferência recebidas via Pix que os clientes faziam. Apesar de ja existir solução para isso no mercado, elas envolvem a cobrança de **taxas por operação**.

Com o Pix Verifier é possível minimizar o **risco de fraude**, uma vez que o valor e a confirmação do pagamento é feita sem problemas, ter mais **agilidade** no atendimento pois não é necessário confirma manualmente na conta a transação e mais **tranquilidade**.

## **Requisitos**

O sistema foi projetado para ser executado **localmente ou na rede local**.

!!! warning
    Esse sistema não foi projetado para ser disponibilizado públicamente na internet, não foram implementadas seguranças adicionais necessárias para esse cenário!

| Requisito | Versão |
| --------- | ------ |
| S.O.      | Windows ou Linux **(ADM64)**|
| PHP       | 8.3.8 |
| Python    | 3.12.8 |
| PostgreSQL| 16 |

Os recursos de hardware são o necessário para o seu sistema operacional funcionar, como trata-se de uma aplicação local com número limitado de usuários não é esperando consumo elevado de recursos.

## **Bancos suportados**

Atualmente essa solução atende apenas os clientes correntistas **PJ** do **Banco Inter**, pois as APIs disponibilizadas pelo banco não permitem acesso as contas pessoa física. O sistema foi estruturado de forma que seja simplificada a inclusão de novos bancos, contudo como esse é um projeto pessoal estou limitado aos bancos que trabalho para fazer a integração.