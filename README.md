Task description:

There are "find products" (findProducts) rules, according to which you should find products that meet the given criteria. We match products according to the rules for each of these products
"Match Products" (matchProducts).
Reserved words:
The equals parameter in the criteria list can contain specific values ​​or reserved words:
- any - means that the parameter must exist and its value is any,
- is empty - means that this parameter cannot be on the list of product parameters,

- this (only in the matchProducts section) - means that the value of a given parameter must be the same as the value of the product parameter for which we are checking
  fit.


Program to write:
The task is to write a Php7 program that will be run with
console level with two parameters, path to * .json file with product list and
path to * .json file with rules list.
php your_program.php /path/to/products.json /path/to/rule.json
The result (without any additional information) should be written to the standard
output in JSON format.
It is important that the program executes in the shortest possible time so that it can
tie large numbers of products together (in second file there was more or less 200 000 records).
