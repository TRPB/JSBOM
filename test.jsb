
function something() {


}

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
