function BaseObject(name, classExtends, classImplements, classFlags) {
	var ___className = name;
	
	this.constructor = null;
	
	this.properties = new Array();
	this.methods = new Array();
	
		
	this.setConstructor = function(constructor) {
		
	};
	
	
	this.construct = function(args) {
		
			
	}
	
	this.addMethod = function(name, visibility, returnType, body, args) {
		var self = this;
		return function F() {
			if (!self.checkVisibility(visibility, F.caller, name)) throw "Unable to call " + visibility + " method from " + F.caller.name + " context";
				
			
			for (var i = 0; i < arguments.length; i++) {
				
			}
		
			var returnValue = body.apply(self, arguments);
			
			if (returnType == 'mixed') return returnValue;
			else if (typeof(returnValue) == returnType) return returnValue;
			else if (typeof(returnValue) == 'object' && returnValue.constructor.name == returnType) return returnValue;
			else if (returnType == 'void' && returnValue == null) return;
			throw "Invalid return value: " + typeof(returnValue) + " expecting " + returnType;
			
		};
	}
	
	this.addProperty = function(name, type, visibility, optional, defaultValue) {
		this.properties[name] = '';
		var self = this;
		var pName = name;
				
		Object.defineProperty(this, name, {
		    get: function() { 
		    	return self.properties[pName];		    
		    },
		    set: function f(value) { 
		    	if (self.checkVisibility(visibility, f.caller, pName)) {
		    		self.properties[pName] = value;
		    	}
		    }
		  });
	};
	
	this.checkVisibility = function(visibility, caller, name) {
		if (visibility == BaseObject.VISIBILITY_PUBLIC) return true;
		if (visibility == BaseObject.VISIBILITY_PRIVATE) {
			if (caller && caller.name && caller.name == ___className) return true;
			else return false;
		}
	};
	
	this.exception = function(name) {
		throw {
			message: name,
			name: 'OOP Error'
		};
	}
	
	this.getGetSet = function(name) {
		var self = this;
		return{
		    get: function() { 
		    	return self[name];
		    },
		    set: function(value) { self[name] = value; }
		  }
	}
}
BaseObject.VISIBILITY_PUBLIC = 'public';
BaseObject.VISIBILITY_PRIVATE = 'private';
BaseObject.VISIBILITY_PROTECTED = 'protected';