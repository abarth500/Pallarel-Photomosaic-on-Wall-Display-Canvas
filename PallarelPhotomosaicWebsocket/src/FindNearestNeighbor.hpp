#ifndef FIND_NEAREST_NEIGHBOR_HPP
#define FIND_NEAREST_NEIGHBOR_HPP

#include <boost/shared_ptr.hpp>
#include <flann/flann.hpp>

using namespace std;
using namespace boost;


namespace PallarelPhotomosaicWebsocket {

class FindNearestNeighbor {
public:
	static const bool DEBUG = false;
	int minValue;
	int maxValue;
	int dimension;
	FindNearestNeighbor();
	virtual ~FindNearestNeighbor();
	void input(int *targetData,string *targetKeys,int nInput,int dim);
	void inputRandom(int nInput, int dim);
	string find(int *queryData,int nQuery,int nResult);
	string findRandom(int nQuery,int nResult);
	flann::Matrix<int> dataset;
	flann::Index<flann::L2<int>> *index;
private:
	int numResult;
	int numInput;
	int numQuery;
	int *queryData;
	int *targetData; 
	string *targetKeys;

};

typedef boost::shared_ptr<FindNearestNeighbor> FindNearestNeighbor_ptr;

}

#endif // FIND_NEAREST_NEIGHBOR_HPP
