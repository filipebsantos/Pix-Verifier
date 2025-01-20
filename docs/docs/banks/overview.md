# **Visão Geral**

## **Como o Pix Verifier funciona?**

O sistema atua monitorando o extrato da conta e filtrando apenas as transações do tipo Pix, e o acesso a conta é por meio das _APIs_ disponibilizadas pelas instituições financeiras, que determinam como e quem deve acessá-las.

Normalmente as _IFs_ (1) também disponilizam o cadastramento de um _webhook_ (2), dessa forma você é avisado pelo banco quando recebe uma transação. Esse parece ser o caminho mais lógico a se seguir, contudo, criar um _webhook_ implica em disponibilizar um serviço na internet acarretando em custos adicionais com a contratação de VPS (_Virtual Private Server_) (3) ou hospedagens. 
{ .annotate }

1.  **IF**: Instituição Financeira.

2.  **Webhook**: É um ponto de entrada de uma API, onde outros sistemas podem enviar informações.

3. **VPS**: Uma máquina virtual que é executada em um _data center_, possui alta disponibilidade e pode ser acessada publicamente pela internet.

Sendo assim, a consulta de extratos é mais simples e obtém o mesmo resultado esperado sem nenhum custo adicional.

## **Bancos suportados**

Neste momento o único banco suportado é o [Banco Inter](inter.md), pois fico limitado ao banco que tenho movimentações para poder fazer a integração e testes, contudo o Pix Verifier foi projetado para que fosse possível a inclusão de novas instituições financeiras. Acesse a página de [Integração](../integration.md) para saber mais.