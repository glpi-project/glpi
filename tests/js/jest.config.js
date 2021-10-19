module.exports = {
   setupFilesAfterEnv: ["jest-extended"],
   setupFiles: ['<rootDir>/bootstrap.js'],
   transform: {
      '^.+\\.js$': 'babel-jest',
   },
   testEnvironment: 'jsdom',
   slowTestThreshold: 10,
};
