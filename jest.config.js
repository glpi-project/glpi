module.exports = {
   setupFilesAfterEnv: ["jest-extended"],
   setupFiles: ['<rootDir>/tests/js/kanban/bootstrap.js'],
   transform: {
      '^.+\\.js$': 'babel-jest',
   },
};
