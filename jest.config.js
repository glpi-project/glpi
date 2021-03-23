//TODO Use multiple configs (one for Kanban, etc) or "projects" when we can get code coverage working with that setup
// to avoid having unneeded setup files. We don't need the Kanban bootstrap if we aren't testing the Kanban
module.exports = {
   setupFilesAfterEnv: ["jest-extended"],
   setupFiles: ['<rootDir>/tests/js/bootstrap.js', '<rootDir>/tests/js/kanban/bootstrap.js'],
   transform: {
      '^.+\\.js$': 'babel-jest',
   },
   testEnvironment: 'jsdom',
   slowTestThreshold: 10,
   collectCoverage: true,
   coverageReporters: ['json', 'html'],
   coverageDirectory: '<rootDir>/tests/js-coverage'
};
