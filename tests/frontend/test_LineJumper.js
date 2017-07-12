describe('LineJumper', function() {
    var obj;

    before(function() {
        obj = new LineJumper({parent: null});
    });

    describe('#mbox', function() {
        it('should be instantiated', function() {
            expect(obj.mbox).to.be.not.null;
        });

        it('should contain the proper form', function() {
            var content = obj.mbox.content;
            expect(content).to.be.not.empty;
            expect(content.getElement('input[name="jumpTo"]')).to.be.not.empty;
        });
    });

    describe('#open', function() {
        it('should not throw exceptions', function() {
            obj.open();
        });
    });

    it('should work with multiple instances', function() {
        var obj1 = new LineJumper({parent: null});
        var obj2 = new LineJumper({parent: null});
        expect(obj1.mbox.content.getElement('input[name="jumpTo"]')).to.be.not.empty;
        expect(obj2.mbox.content.getElement('input[name="jumpTo"]')).to.be.not.empty;
    });
});
