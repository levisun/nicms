/**
 * 数据扩展
 */
class extend {
    constructor(destination, source) {
        return this.extend(destination, source);
    }

    extend(destination, source) {
        for (var property in source) {

            if (this.isArray(source[property])) {
                destination[property] = JSON.parse(JSON.stringify(source[property]));
            } else if (this.isObject(source[property])) {
                destination[property] = this.extend([], source[property]);
            } else {
                destination[property] = source[property];
            }
        }

        return destination;
    }

    isArray(name) {
        return "Array" === Object.prototype.toString.call(name).slice(8, -1);
    }

    isObject(name) {
        return "Object" === Object.prototype.toString.call(name).slice(8, -1);
    }

    isBoolean(name) {
        return "Boolean" === Object.prototype.toString.call(name).slice(8, -1);
    }

    isNull(name) {
        return "Null" === Object.prototype.toString.call(name).slice(8, -1);
    }
}
