describe('cora.supportedTagsets', function() {
    it('should provide an Array of supported tagsets', function() {
        expect(cora).to.have.property('supportedTagsets');
        expect(cora.supportedTagsets).to.be.an('Array');
    });
});

describe('cora.importableTagsets', function() {
    it('should provide an Array of importable tagsets', function() {
        expect(cora).to.have.property('importableTagsets');
        expect(cora.importableTagsets).to.be.an('Array');
    });
});

describe('Tagset', function() {
    var tagset;
    var sample_data = {
        id: 42,
        name: "My sample tagset",
        class: "pos",
        set_type: "closed",
        tags: [
            {id: 100, value: "AA", needs_revision: "0"},
            {id: 101, value: "BB", needs_revision: "0"},
            {id: 102, value: "CC", needs_revision: "0"}
        ]
    };

    before(function() {
        tagset = new Tagset(sample_data);
    });

    describe('#needsProcessing', function() {
        it('should report a closed tagset as needing processing', function() {
            expect(tagset.needsProcessing()).to.be.true;
        });
    });
});
