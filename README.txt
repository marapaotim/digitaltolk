Thought about the code: it's good that it uses repository pattern. my suggestions is to have a Service side to put all the business logic and formatting of codes instead of putting it in repository and controller (I couldn't do this because no time remains). Also use camelCase for variable naming instead of Snake Case as the PSR suggest. Also make a reusable method to shortened the code.

Refactor Code For app/Http/Controllers/BookingController.php:
 - Refactor some of the if else code to ternary condition to shortened the code.
 - Implement private reusable code like getRequestAndUser and getRequestAndResponse to remove repetitive codes
 - remove the function parameters Request $request to implement it in constructor and to implement the reusable code
 - remove some of the nested if else condition to for easy reading of codes

 Refactor Code For app/Repository/BookingRepository.php:
  - Refactor some of the if else code to ternary condition to shortened the code.
  - change snake case variable to camel case to follow the PSR 
  - implement public method for constuctor to follow the PSR

Thank you!
