UPDATE resultado_faixas
SET maximo = 33.99
WHERE codigo = 'RUIM'
  AND minimo = 0.00
  AND maximo = 33.00;

UPDATE resultado_faixas
SET maximo = 66.99
WHERE codigo = 'REGULAR'
  AND minimo = 34.00
  AND maximo = 66.00;
