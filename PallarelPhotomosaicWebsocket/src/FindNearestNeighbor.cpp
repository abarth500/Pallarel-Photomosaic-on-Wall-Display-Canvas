#include <sstream>
#include <string>
#include <boost/progress.hpp>
#include <boost/random.hpp>
#include <boost/asio.hpp>
#include <flann/flann.hpp>
#include "FindNearestNeighbor.hpp"

using PallarelPhotomosaicWebsocket::FindNearestNeighbor;
using namespace std;
using namespace boost;

FindNearestNeighbor::FindNearestNeighbor(){
	minValue = 0;
	maxValue = 255;
}

FindNearestNeighbor::~FindNearestNeighbor(){
	delete[] queryData;
	delete[] targetData;
	delete[] targetKeys;
}

void FindNearestNeighbor::input(int *tData,string *tKeys,int nInput,int dim){
	boost::progress_timer t;
	targetKeys = tKeys;
	targetData = tData;
	numInput = nInput;
	dimension = dim;
	dataset = flann::Matrix<int>(targetData, numInput, dimension);
	index = new flann::Index<flann::L2<int>>(dataset,flann::KDTreeIndexParams(8));
	index->buildIndex();
}

string FindNearestNeighbor::find(int *qData,int nQuery,int nResult){
	numQuery = nQuery;
	numResult = nResult;
	queryData = qData;
	if(DEBUG){
		for (int i = 0; i < numQuery; i++) {
			cout << "Q" << i << "\t";
			for (int j = 0; j < dimension; j++) {
				cout << qData[(i * dimension) + j] << "\t";
			}
		}
		cout << endl << endl;
	}
	flann::Matrix<int> query(queryData, numQuery, dimension);
	flann::Matrix<int> indices(new int[query.rows * numResult], query.rows,
			numResult);
	flann::Matrix<float> dists(new float[query.rows * numResult], query.rows,
			numResult);
	index->knnSearch(query, indices, dists, numResult, flann::SearchParams(128));
	
	stringstream res;
	res << "[";
	for (int q = 0; q < numQuery; q++) {
		if(q != 0){
			res << ",";
		}
		for (int r = 0; r < numResult; r++) {
			if(DEBUG){
				cout << "Q";
				cout << q;
				cout << "#";
				cout << indices[q][r] << "\t";
			}
			string direction = "";
			for (int j = 0; j < dimension; ++j) {
				if(query[q][j] >= dataset[indices[q][r]][j]){
					direction.append("1");
				}else{
					direction.append("0");
				}
			}
			if(DEBUG){
				cout << endl << "\tdist:\t" << sqrt(dists[q][r]) << endl;
				cout << "\tkey:\t" << targetKeys[indices[q][r]] <<endl;
				cout << "\tdir:\t" << direction << endl << endl;
			}
			res << "{\"distance\":" << sqrt(dists[q][r]) << ",";
			res << "\"key\":\"" << targetKeys[indices[q][r]] << "\",";
			res << "\"direction\":\"" << direction << "\"}";
		}
	}
	res << "]";
	return res.str();
}

void FindNearestNeighbor::inputRandom(int nInput,int dim){
	boost::progress_timer t;
	mt19937 gen(static_cast<unsigned long>(time(0)));
	uniform_smallint<> dst(minValue,maxValue);
	variate_generator<mt19937&, uniform_smallint<> > rand(gen, dst);
	int *tData = new int[(nInput * dim)];
	string *tKeys = new string[(nInput * dim)];
	for (int i = 0; i < nInput; ++i) {
		for (int j = 0; j < dim; ++j) {
			tData[i * dim + j] = rand();
			stringstream ss;
			ss << (i * dim + j);
			tKeys[i * dim + j] = ss.str();
		}
	}
	input(tData,tKeys,nInput,dim);
}


string FindNearestNeighbor::findRandom(int nQuery,int nResult){
	mt19937 gen(static_cast<unsigned long>(time(0)));
	uniform_smallint<> dst(minValue,maxValue);
	variate_generator<mt19937&, uniform_smallint<> > rand(gen, dst);
	int *qData = new int[dimension];
	for (int i = 0; i < nQuery; i++) {
		//cout << "Q";
		//cout << i << "\t";
		for (int j = 0; j < dimension; j++) {
			qData[(i * dimension) + j] = rand();
		//	cout << qData[(i * dimension) + j] << "\t";
		}
		//cout << endl;
	}
	return find(qData,nQuery,nResult);
}