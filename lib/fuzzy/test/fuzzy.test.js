var fuzzy = fuzzy || require('../lib/fuzzy')
  , chai = chai || require('chai')
  , expect = chai.expect
  , _ = _ || require('underscore');

describe('fuzzy', function(){
  describe('.test', function(){
    it('should return true when fuzzy match', function(){
      expect(fuzzy.test('back', 'imaback')).to.be.True;
      expect(fuzzy.test('back', 'bakck')).to.be.True;
      expect(fuzzy.test('shig', 'osh kosh modkhigow')).to.be.True;
      expect(fuzzy.test('', 'osh kosh modkhigow')).to.be.True;
    });
    it('should return false when no fuzzy match', function(){
      expect(fuzzy.test('back', 'abck')).to.be.False;
      expect(fuzzy.test('okmgk', 'osh kosh modkhigow')).to.be.False;
    });
  });
  describe('.simpleFilter', function(){
    it('should filter the elements of a string array', function(){
      expect(fuzzy.simpleFilter('a', ['a'])).to.eql(['a']);
      expect(fuzzy.simpleFilter('ab', ['aba', 'c', 'cacb'])).to.eql(['aba', 'cacb']);
    });
  });
  describe('.match', function(){
    it('should return the rendered string and match score', function(){
      var results = fuzzy.match('ab', 'ZaZbZ', {pre: '<', post: '>'});
      expect(results.rendered).to.equal('Z<a>Z<b>Z');
      expect(results).to.include.keys('score');
    });
    it('should not require a template, returning the string as is', function(){
      expect(fuzzy.match('ab', 'ZaZbZ').rendered).to.equal('ZaZbZ');
    });
    it('should return null on no match', function(){
      expect(fuzzy.match('ZEBRA!', 'ZaZbZ')).to.equal(null);
    });
    it('should return a greater score for consecutive matches of pattern', function(){
      var consecutiveScore = fuzzy.match('abcd', 'zabcd').score;
      var scatteredScore = fuzzy.match('abcd', 'azbcd').score;
      expect(consecutiveScore).to.be.above(scatteredScore);
    });
    it('should be case insensitive by default', function(){
      expect(fuzzy.filter('a', ['A'])[0].string).to.equal('A');
    });
    it('should take an ignoreCase parameter', function(){
      var opts = {caseSensitive: true};
      expect(fuzzy.match('Ab', 'aB', opts)).to.equal(null);
      opts.caseSensitive = false;
      expect(fuzzy.match('AB', 'AB', opts)).to.not.equal(null);
    });
    xit('should return the same score for matches in the middle as matches at beginning', function(){
      // TODO: Dont know how I feel about this. Sublime weights characters that
      // appear toward the beginning of the string a bit higher
    });
    // TODO: implement this test
    xit('should prefer consecutive characters even if they come after the first match', function(){
      var opts = {pre: '<', post: '>'};
      var result = fuzzy.match('bass', 'bodacious bass', opts).rendered;
      expect(result).to.equal('bodacious <b><a><s><s>');
    });
    xit('should prefer consecutive characters in a match even if we need to break up into a substring', function(){
      var opts = {pre: '<', post: '>'};
      var result = fuzzy.match('reic', 'reactive rice', opts).rendered;
      expect(result).to.equal('<r><e>active r<i><c>e');
    });
  });
  describe('.filter', function(){
    it('should return list untouched when the pattern is undefined', function() {
      var arr = ['aba', 'c', 'cacb'];
      var result = fuzzy.filter(undefined, arr);
      expect(result).to.equal(arr);
    });
    it('should return an empty array when the array is undefined', function() {
      var result = fuzzy.filter('pattern', undefined);
      expect(result).to.deep.equal([]);
    });
    it('should return an empty array when the array is empty', function() {
      var result = fuzzy.filter('pattern', []);
      expect(result).to.deep.equal([]);
    });
    it('should return the index and matching array elements', function(){
      var result = fuzzy.filter('ab', ['aba', 'c', 'cacb']);
      expect(result).to.have.length(2);

      // verify first result
      expect(result[0].string).to.equal('aba');
      expect(result[0].index).to.equal(0);
      expect(result[0]).to.have.property('score');

      // verify second result
      expect(result[1].string).to.equal('cacb');
      expect(result[1].index).to.equal(2);
      expect(result[1]).to.have.property('score');
    });
    it('should use optional template stringing to wrap each element', function(){
      var rendered = fuzzy.filter('a', ['a'], {
          pre: "tah"
        , post: "zah!"
      })[0].string;
      expect(rendered).to.equal('tahazah!');

      rendered = fuzzy.filter('ab', ['cacbc'], {
          pre: "<"
        , post: ">"
      })[0].string;
      expect(rendered).to.eql('c<a>c<b>c');
    });
    it('should use optional func to get string out of array entry', function() {
      var arr = [{arg: 'hizzahpooslahp'}, {arg: 'arppg'}];
      expect(fuzzy.filter('poop', arr, {
        extract: function(original) {
          return original.arg;
        }
      })[0].string).to.equal('hizzahpooslahp');
    });
    it('should return list untouched when given empty pattern', function(){
      // array needs to be over size 10: V8 has stable sort with < 10 elements,
      // unstable with > 10 elements
      var arr = 'abcdefghjklmnop'.split('');
      var results = _.pluck(fuzzy.filter('', arr), 'string');
      expect(results).to.eql(arr);
    });
    it('should weight exact matches the highest', function(){
      // array needs to be over size 10: V8 has stable sort with < 10 elements,
      // unstable with > 10 elements
      var searchString = 'go';
      var arr = 'abcdefghjklmnop'.split('');
      arr = arr.map(function(item) { return item + 'oo'; });
      arr = arr.concat(['good', 'go', 'goofgo', 'ogo']);
      var results = _.pluck(fuzzy.filter(searchString, arr), 'string');
      expect(results[0]).to.eql(searchString);
    });
  });
});
