JSBOM
=====

A better object model for javascript- Work In Progress


This is an attempt to build a better object model into javascript with proper classes with methods and properties. 

It currently supports:

- Classes
- Objects
- Properties
- Methods
- Visibility


Example
-------

```
class Person {
	private string name;
	
	public void setName(string name) {
		this.name = name;
	}
	
	
	public string getName() {

		return this.name;	
	}
}

class Animal {
	private string name;
}


window.onload = function() {
	var p = new Person();
	
	p.setName('Tom');

	alert(p.getName());
}
```


Usage
-----

Include the file on your page through jsbomb.php and it's compiled on the fly

```html
<script src="jsbomb.php?file=test.jsb"></script>

```

Alternatively, set up rewrite rules for .jsb files.
